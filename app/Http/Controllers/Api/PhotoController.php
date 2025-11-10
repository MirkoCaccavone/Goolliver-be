<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhotoUploadRequest;
use App\Http\Requests\UpdatePhotoRequest;
use App\Models\Contest;
use App\Models\Entry;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            $contest = Contest::findOrFail($request->contest_id);

            // Check if user can participate in this contest
            if (!$contest->isActive() || !$contest->canUserParticipate(Auth::user())) {
                return response()->json([
                    'error' => 'Non puoi partecipare a questo contest',
                    'code' => 'CONTEST_NOT_AVAILABLE'
                ], 403);
            }

            // Check if user already has an entry for this contest
            $existingEntry = Entry::where('user_id', Auth::id())
                ->where('contest_id', $contest->id)
                ->first();

            if ($existingEntry) {
                return response()->json([
                    'error' => 'Hai già caricato una foto per questo contest',
                    'code' => 'ENTRY_ALREADY_EXISTS'
                ], 409);
            }

            // Upload and process photo
            $photoData = $this->photoService->uploadPhoto(
                $request->file('photo'),
                $request->validated(),
                Auth::id()
            );

            // Create entry
            $entry = Entry::create([
                'user_id' => Auth::id(),
                'contest_id' => $contest->id,
                'title' => $request->title,
                'description' => $request->description,
                'photo_url' => $photoData['photo_url'],
                'thumbnail_url' => $photoData['thumbnail_url'],
                'file_size' => $photoData['file_size'],
                'mime_type' => $photoData['mime_type'],
                'dimensions' => $photoData['dimensions'],
                'moderation_score' => $photoData['moderation_score'],
                'moderation_status' => $photoData['moderation_status'],
                'processing_status' => $photoData['processing_status'],
                'metadata' => $photoData['metadata']
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
                    'created_at' => $entry->created_at
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Dati non validi',
                'code' => 'VALIDATION_ERROR',
                'details' => $e->errors()
            ], 422);
        } catch (\App\Exceptions\PhotoUploadException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode()
            ], 400);
        } catch (\Exception $e) {
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
}
