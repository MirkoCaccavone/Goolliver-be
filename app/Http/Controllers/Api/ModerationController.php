<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entry;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ModerationController extends Controller
{
    protected ModerationService $moderationService;

    public function __construct(ModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Lista foto in attesa di moderazione
     */
    public function pendingPhotos(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status', 'pending_review');

        $photos = Entry::where('moderation_status', $status)
            ->with(['user:id,name,email', 'contest:id,title'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $photos->items(),
            'pagination' => [
                'current_page' => $photos->currentPage(),
                'per_page' => $photos->perPage(),
                'total' => $photos->total(),
                'last_page' => $photos->lastPage(),
                'has_more' => $photos->hasMorePages()
            ],
            'summary' => $this->getModerationSummary()
        ]);
    }

    /**
     * Dettagli di moderazione per una foto specifica
     */
    public function photoDetails(int $entryId): JsonResponse
    {
        $entry = Entry::with(['user:id,name,email', 'contest:id,title'])
            ->findOrFail($entryId);

        $moderationData = $entry->metadata['moderation'] ?? [];

        return response()->json([
            'status' => 'success',
            'data' => [
                'entry' => $entry,
                'moderation' => [
                    'score' => $entry->moderation_score,
                    'status' => $entry->moderation_status,
                    'provider' => $moderationData['provider'] ?? 'unknown',
                    'confidence' => $moderationData['confidence'] ?? 0,
                    'categories' => $moderationData['categories'] ?? [],
                    'flagged_reasons' => $moderationData['flagged_reasons'] ?? [],
                    'processing_time_ms' => $moderationData['processing_time_ms'] ?? 0,
                ],
                'history' => $this->getModerationHistory($entryId)
            ]
        ]);
    }

    /**
     * Approva una foto
     */
    public function approvePhoto(Request $request, int $entryId): JsonResponse
    {
        $entry = Entry::findOrFail($entryId);

        if ($entry->moderation_status === 'approved') {
            return response()->json([
                'status' => 'info',
                'message' => 'Foto già approvata'
            ]);
        }

        $entry->update([
            'moderation_status' => 'approved',
            'metadata' => array_merge($entry->metadata ?? [], [
                'manual_approval' => [
                    'approved_by' => Auth::id(),
                    'approved_at' => now()->toISOString(),
                    'reason' => $request->get('reason', 'Approvazione manuale'),
                    'notes' => $request->get('notes')
                ]
            ])
        ]);

        Log::info('Photo manually approved', [
            'entry_id' => $entryId,
            'approved_by' => Auth::id(),
            'reason' => $request->get('reason')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Foto approvata con successo',
            'data' => $entry->fresh()
        ]);
    }

    /**
     * Rifiuta una foto
     */
    public function rejectPhoto(Request $request, int $entryId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'category' => 'required|string|in:adult,violence,hatred,harassment,self_harm,illegal,spam,inappropriate'
        ]);

        $entry = Entry::findOrFail($entryId);

        // Log di debug per verificare la relazione e lo stato
        Log::info('DEBUG REJECT', [
            'entry_id' => $entryId,
            'entry_user_id' => $entry->user_id,
            'entry_payment_status' => $entry->payment_status,
            'entry_credit_given' => $entry->credit_given ?? false,
            'user_found' => $entry->user ? $entry->user->email : null,
            'user_photo_credits_before' => $entry->user ? $entry->user->photo_credits : null,
        ]);
        $request->validate([
            'reason' => 'required|string|max:500',
            'category' => 'required|string|in:adult,violence,hatred,harassment,self_harm,illegal,spam,inappropriate'
        ]);

        $entry = Entry::findOrFail($entryId);

        if ($entry->moderation_status === 'rejected') {
            return response()->json([
                'status' => 'info',
                'message' => 'Foto già rifiutata'
            ]);
        }

        // Assegna credito solo se la foto è stata pagata e non ha già ricevuto credito
        $user = $entry->user;
        $creditGiven = $entry->credit_given ?? false;
        $wasPaid = $entry->payment_status === 'completed';

        if ($wasPaid && !$creditGiven) {
            // Incrementa i photo_credits dell'utente
            $user->increment('photo_credits');
            // Segna la foto come creditata
            $entry->credit_given = true;
        }

        $entry->moderation_status = 'rejected';
        $entry->metadata = array_merge($entry->metadata ?? [], [
            'manual_rejection' => [
                'rejected_by' => Auth::id(),
                'rejected_at' => now()->toISOString(),
                'reason' => $request->reason,
                'category' => $request->category,
                'notes' => $request->get('notes'),
                'notify_user' => $request->boolean('notify_user', true)
            ]
        ]);
        $entry->save();

        Log::info('Photo manually rejected', [
            'entry_id' => $entryId,
            'rejected_by' => Auth::id(),
            'reason' => $request->reason,
            'category' => $request->category,
            'credit_given' => $entry->credit_given,
            'user_photo_credits' => $user->photo_credits
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Foto rifiutata con successo',
            'data' => $entry->fresh()
        ]);
    }

    /**
     * Rimetti in coda per nuova analisi
     */
    public function reanalyzePhoto(int $entryId): JsonResponse
    {
        $entry = Entry::findOrFail($entryId);

        try {
            // Ricarica il file e ri-analizza
            $photoPath = storage_path('app/public/photos/' . $entry->photo_url);

            if (!file_exists($photoPath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File foto non trovato per ri-analisi'
                ], 404);
            }

            $tempFile = new \Illuminate\Http\UploadedFile(
                $photoPath,
                basename($photoPath),
                $entry->mime_type,
                null,
                true
            );

            $result = $this->moderationService->moderatePhoto($tempFile);

            $entry->update([
                'moderation_status' => $result['status'],
                'moderation_score' => $result['overall_score'],
                'metadata' => array_merge($entry->metadata ?? [], [
                    'reanalysis' => [
                        'reanalyzed_by' => Auth::id(),
                        'reanalyzed_at' => now()->toISOString(),
                        'previous_status' => $entry->moderation_status,
                        'previous_score' => $entry->moderation_score,
                        'new_result' => $result
                    ]
                ])
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Foto ri-analizzata con successo',
                'data' => [
                    'entry' => $entry->fresh(),
                    'analysis_result' => $result
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Photo reanalysis failed', [
                'entry_id' => $entryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Errore durante ri-analisi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiche di moderazione
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_photos' => Entry::count(),
            'by_status' => [
                'approved' => Entry::where('moderation_status', 'approved')->count(),
                'pending' => Entry::where('moderation_status', 'pending')->count(),
                'pending_review' => Entry::where('moderation_status', 'pending_review')->count(),
                'rejected' => Entry::where('moderation_status', 'rejected')->count(),
            ],
            'score_distribution' => [
                'safe' => Entry::where('moderation_score', '<=', 0.2)->count(),
                'moderate' => Entry::whereBetween('moderation_score', [0.2, 0.7])->count(),
                'high_risk' => Entry::where('moderation_score', '>', 0.7)->count(),
            ],
            'recent_activity' => [
                'today' => Entry::whereDate('created_at', today())->count(),
                'week' => Entry::whereDate('created_at', '>=', now()->subWeek())->count(),
                'month' => Entry::whereDate('created_at', '>=', now()->subMonth())->count(),
            ],
            'provider_usage' => $this->getProviderUsageStats()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
            'moderation_config' => $this->moderationService->getConfig()
        ]);
    }

    /**
     * Configurazione moderazione
     */
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->moderationService->getConfig()
        ]);
    }

    /**
     * Aggiorna configurazione moderazione
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $request->validate([
            'auto_approve_threshold' => 'numeric|between:0,1',
            'auto_reject_threshold' => 'numeric|between:0,1',
            'require_manual_review' => 'boolean'
        ]);

        $this->moderationService->setConfig($request->only([
            'auto_approve_threshold',
            'auto_reject_threshold',
            'require_manual_review'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Configurazione aggiornata con successo'
        ]);
    }

    /**
     * Riepilogo moderazione
     */
    protected function getModerationSummary(): array
    {
        return [
            'pending_review' => Entry::where('moderation_status', 'pending_review')->count(),
            'pending' => Entry::where('moderation_status', 'pending')->count(),
            'high_risk' => Entry::where('moderation_score', '>', 0.7)
                ->whereIn('moderation_status', ['pending', 'pending_review'])
                ->count(),
        ];
    }

    /**
     * Storia moderazione per una foto
     */
    protected function getModerationHistory(int $entryId): array
    {
        $entry = Entry::find($entryId);
        $history = [];

        if ($entry && isset($entry->metadata['manual_approval'])) {
            $history[] = [
                'action' => 'approved',
                'timestamp' => $entry->metadata['manual_approval']['approved_at'],
                'by_user' => $entry->metadata['manual_approval']['approved_by'],
                'reason' => $entry->metadata['manual_approval']['reason']
            ];
        }

        if ($entry && isset($entry->metadata['manual_rejection'])) {
            $history[] = [
                'action' => 'rejected',
                'timestamp' => $entry->metadata['manual_rejection']['rejected_at'],
                'by_user' => $entry->metadata['manual_rejection']['rejected_by'],
                'reason' => $entry->metadata['manual_rejection']['reason']
            ];
        }

        if ($entry && isset($entry->metadata['reanalysis'])) {
            $history[] = [
                'action' => 'reanalyzed',
                'timestamp' => $entry->metadata['reanalysis']['reanalyzed_at'],
                'by_user' => $entry->metadata['reanalysis']['reanalyzed_by'],
                'previous_status' => $entry->metadata['reanalysis']['previous_status']
            ];
        }

        return collect($history)->sortByDesc('timestamp')->values()->toArray();
    }

    /**
     * Statistiche utilizzo provider
     */
    protected function getProviderUsageStats(): array
    {
        // Per ora mock, in futuro potremmo salvare queste info
        return [
            'openai' => ['count' => 150, 'avg_time_ms' => 1200],
            'mock' => ['count' => 50, 'avg_time_ms' => 100],
        ];
    }
}
