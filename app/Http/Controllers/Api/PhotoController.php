<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhotoUploadRequest;
use App\Http\Requests\UpdatePhotoRequest;
use App\Models\Contest;
use App\Models\Entry;
use App\Models\User;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    public function __construct(
        private PhotoService $photoService
    ) {}

    /**
     * Upload a new photo for a contest entry
     */
    public function upload(PhotoUploadRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            Log::info('PhotoController upload started', [
                'user_id' => Auth::id(),
                'contest_id' => $request->contest_id,
                'has_photo' => $request->hasFile('photo')
            ]);

            $contest = Contest::findOrFail($request->contest_id);

            // Debug contest status
            $isActive = $contest->isActive();
            $canParticipate = $contest->canUserParticipate(Auth::user());

            Log::info('Contest participation check', [
                'user_id' => Auth::id(),
                'contest_id' => $contest->id,
                'contest_status' => $contest->status,
                'start_date' => $contest->start_date,
                'end_date' => $contest->end_date,
                'current_participants' => $contest->current_participants,
                'max_participants' => $contest->max_participants,
                'is_active' => $isActive,
                'can_participate' => $canParticipate,
                'now' => now()
            ]);

            // Check if user can participate in this contest
            if (!$isActive || !$canParticipate) {
                return response()->json([
                    'error' => 'Non puoi partecipare a questo contest',
                    'code' => 'CONTEST_NOT_AVAILABLE',
                    'details' => [
                        'is_active' => $isActive,
                        'can_participate' => $canParticipate
                    ]
                ], 403);
            }

            // Elimina tutte le entry 'pending' per questo utente/contest (pulizia orfani da errori Stripe)
            Entry::where('user_id', Auth::id())
                ->where('contest_id', $contest->id)
                ->where('payment_status', 'pending')
                ->delete();

            // Check if user already has a completed entry for this contest
            $completedEntry = Entry::where('user_id', Auth::id())
                ->where('contest_id', $contest->id)
                ->where('payment_status', 'completed')
                ->orderByDesc('id')
                ->first();

            // Permetti nuova partecipazione se non esiste entry completata o se l'ultima è stata rifiutata
            if ($completedEntry && $completedEntry->moderation_status !== 'rejected') {
                return response()->json([
                    'error' => 'Hai già caricato una foto per questo contest',
                    'code' => 'ENTRY_ALREADY_EXISTS'
                ], 409);
            }

            // 💳 GESTIONE PAGAMENTO CON CREDITI
            $user = User::findOrFail(Auth::id()); // Prende il modello completo
            $paymentMethod = $request->input('payment_method', 'card'); // 'credit' o 'card'
            $useCredits = $paymentMethod === 'credit';

            if ($useCredits) {
                // Verifica che l'utente abbia almeno 1 credito
                if ($user->photo_credits < 1) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Non hai abbastanza crediti per caricare una foto',
                        'current_credits' => $user->photo_credits,
                        'required_credits' => 1,
                        'code' => 'INSUFFICIENT_CREDITS'
                    ], 402); // 402 Payment Required
                }

                // Scala il credito PRIMA dell'upload
                $user->decrement('photo_credits');

                // Aggiorna le note sui crediti
                $creditNote = "Credito utilizzato per caricamento foto - Contest: " . $contest->title;
                $existingNotes = $user->credit_notes ? $user->credit_notes . "\n" : '';
                $user->update([
                    'credit_notes' => $existingNotes . date('Y-m-d H:i:s') . ': ' . $creditNote
                ]);

                Log::info('Credito utilizzato per upload', [
                    'user_id' => $user->id,
                    'contest_id' => $contest->id,
                    'credits_remaining' => $user->fresh()->photo_credits
                ]);
            } else {
                // TODO: Gestire pagamento con carta/PayPal
                // Per ora assumiamo che il pagamento sia sempre ok
                Log::info('Pagamento con carta simulato', [
                    'user_id' => $user->id,
                    'contest_id' => $contest->id,
                    'payment_method' => $paymentMethod
                ]);
            }

            // Upload and process photo: crea SEMPRE una entry
            $photoData = array_merge($request->validated(), [
                'payment_status' => $useCredits ? 'completed' : 'pending',
                'payment_method' => $paymentMethod
            ]);
            $entry = $this->photoService->uploadPhoto(
                $request->file('photo'),
                Auth::id(),
                $contest->id,
                $photoData
            );
            DB::commit();


            Log::info('PhotoController - Entry created', [
                'entry_id' => $entry->id,
                'moderation_status' => $entry->moderation_status,
                'processing_status' => $entry->processing_status,
                'payment_status' => $entry->payment_status
            ]);
            return response()->json([
                'message' => 'Foto caricata con successo',
                'entry' => [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'photo_url' => $entry->photo_url,
                    'thumbnail_url' => $entry->thumbnail_url,
                    'processing_status' => $entry->processing_status,
                    'moderation_status' => $entry->moderation_status,
                    'payment_status' => $entry->payment_status,
                    'created_at' => $entry->created_at
                ],
                'payment' => [
                    'method' => $paymentMethod,
                    'credits_used' => $useCredits ? 1 : 0,
                    'credits_remaining' => $user->fresh()->photo_credits
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Dati non validi',
                'code' => 'VALIDATION_ERROR',
                'details' => $e->errors()
            ], 422);
        } catch (\App\Exceptions\PhotoUploadException $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode()
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Photo upload failed', [
                'user_id' => Auth::id(),
                'contest_id' => $request->contest_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Errore durante il caricamento della foto',
                'code' => 'UPLOAD_FAILED'
            ], 500);
        }
    }

    /**
     * Update photo metadata (title, description)
     */
    public function update(UpdatePhotoRequest $request, Entry $entry): JsonResponse
    {
        try {
            // Check ownership
            if ($entry->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Non autorizzato',
                    'code' => 'UNAUTHORIZED'
                ], 403);
            }

            // Check if contest is still active for updates
            if (!$entry->contest->isActive()) {
                return response()->json([
                    'error' => 'Contest non più attivo',
                    'code' => 'CONTEST_INACTIVE'
                ], 403);
            }

            $entry->update($request->validated());

            return response()->json([
                'message' => 'Foto aggiornata con successo',
                'entry' => [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'description' => $entry->description,
                    'updated_at' => $entry->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Photo update failed', [
                'entry_id' => $entry->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Errore durante l\'aggiornamento',
                'code' => 'UPDATE_FAILED'
            ], 500);
        }
    }

    /**
     * Delete photo and entry
     */
    public function destroy(Entry $entry): JsonResponse
    {
        try {
            // Check ownership
            if ($entry->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Non autorizzato',
                    'code' => 'UNAUTHORIZED'
                ], 403);
            }

            // Check if contest allows deletion
            if (!$entry->contest->isActive()) {
                return response()->json([
                    'error' => 'Non puoi eliminare foto da contest non attivi',
                    'code' => 'CONTEST_INACTIVE'
                ], 403);
            }

            // Delete physical files
            $this->photoService->deletePhoto($entry);

            // Decrementa current_participants del contest
            $contest = $entry->contest;
            if ($contest && $contest->current_participants > 0) {
                $contest->decrement('current_participants');
            }
            // Delete entry
            $entry->delete();

            return response()->json([
                'message' => 'Foto eliminata con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Photo deletion failed', [
                'entry_id' => $entry->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Errore durante l\'eliminazione',
                'code' => 'DELETE_FAILED'
            ], 500);
        }
    }

    /**
     * Get photo details
     */
    public function show(Entry $entry): JsonResponse
    {
        return response()->json([
            'entry' => [
                'id' => $entry->id,
                'title' => $entry->title,
                'description' => $entry->description,
                'photo_url' => $entry->photo_url,
                'thumbnail_url' => $entry->thumbnail_url,
                'file_size' => $entry->file_size,
                'mime_type' => $entry->mime_type,
                'dimensions' => $entry->dimensions,
                'processing_status' => $entry->processing_status,
                'moderation_status' => $entry->moderation_status,
                'votes_count' => $entry->votes_count,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
                'user' => [
                    'id' => $entry->user->id,
                    'name' => $entry->user->name,
                    'username' => $entry->user->username
                ],
                'contest' => [
                    'id' => $entry->contest->id,
                    'title' => $entry->contest->title
                ]
            ]
        ]);
    }

    /**
     * Get contest photos gallery
     */
    public function gallery(Contest $contest, Request $request): JsonResponse
    {
        $query = Entry::with(['user:id,name,username'])
            ->where('contest_id', $contest->id)
            ->where('moderation_status', 'approved')
            ->where('processing_status', 'completed');

        // Sorting options
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortBy) {
            case 'votes':
                $query->orderBy('votes_count', $sortOrder);
                break;
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $entries = $query->paginate($request->get('per_page', 12));

        return response()->json([
            'entries' => $entries->items(),
            'pagination' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
                'has_more' => $entries->hasMorePages()
            ]
        ]);
    }

    /**
     * Get user's photos
     */
    public function userPhotos(Request $request): JsonResponse
    {
        // Cancella le entry 'pending' SOLO se richiesto (refresh dopo pagamento fallito)
        if ($request->has('cleanup_pending') && $request->cleanup_pending == 1) {
            Entry::where('user_id', Auth::id())
                ->where('payment_status', 'pending')
                ->delete();
        }

        $query = Entry::with(['contest:id,title'])
            ->where('user_id', Auth::id());

        // Filter by contest
        if ($request->has('contest_id')) {
            $query->where('contest_id', $request->contest_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('moderation_status', $request->status);
        }

        $entries = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'entries' => $entries->items(),
            'pagination' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
                'has_more' => $entries->hasMorePages()
            ]
        ]);
    }

    /**
     * Get upload progress (for large files) - placeholder for future implementation
     */
    public function uploadProgress(Request $request): JsonResponse
    {
        $uploadId = $request->get('upload_id');

        if (!$uploadId) {
            return response()->json(['error' => 'Upload ID richiesto'], 400);
        }

        // For now, return a simple response - will be implemented with chunked uploads
        return response()->json([
            'upload_id' => $uploadId,
            'progress' => [
                'percentage' => 100,
                'status' => 'completed',
                'message' => 'Upload completed'
            ]
        ]);
    }
    /**
     * Check photo moderation status
     */
    public function moderationStatus(Entry $entry): JsonResponse
    {
        // Check ownership
        if ($entry->user_id !== Auth::id()) {
            return response()->json([
                'error' => 'Non autorizzato',
                'code' => 'UNAUTHORIZED'
            ], 403);
        }

        return response()->json([
            'entry_id' => $entry->id,
            'moderation_status' => $entry->moderation_status,
            'moderation_score' => $entry->moderation_score,
            'processing_status' => $entry->processing_status,
            'metadata' => $entry->metadata
        ]);
    }

    /**
     * Ottieni i crediti dell'utente corrente
     */
    public function userCredits(): JsonResponse
    {
        $user = User::findOrFail(Auth::id());

        return response()->json([
            'user_id' => $user->id,
            'photo_credits' => $user->photo_credits,
            'credit_notes' => $user->credit_notes,
            'last_updated' => $user->updated_at
        ]);
    }

    /**
     * Restituisce il totale dei voti ricevuti su tutte le foto dell'utente
     */
    public function userVotesSummary(): JsonResponse
    {
        $userId = Auth::id();
        // Somma tutti i voti sulle entries dell'utente
        $totalVotes = \App\Models\Entry::where('user_id', $userId)
            ->where('moderation_status', 'approved')
            ->withCount('votes')
            ->get()
            ->sum('votes_count');

        return response()->json([
            'user_id' => $userId,
            'total_votes' => $totalVotes
        ]);
    }
}
