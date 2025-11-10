<?php

namespace App\Http\Controllers;

use App\Services\VoteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VoteController extends Controller
{
    private VoteService $voteService;

    public function __construct(VoteService $voteService)
    {
        $this->voteService = $voteService;
    }

    /**
     * Toggle like su una foto
     */
    public function toggleLike(Request $request, int $entryId): JsonResponse
    {
        try {
            // Per ora usiamo user_id dal request, poi implementeremo auth
            $userId = $request->input('user_id', 1); // Default user per test

            $result = $this->voteService->toggleLike(
                $entryId,
                $userId,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Verifica stato voto utente nel contest
     */
    public function getUserVoteStatus(Request $request, int $contestId): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'] // Temporaneo per test
        ]);

        try {
            $voteStatus = $this->voteService->getUserVoteInContest(
                $contestId,
                $request->input('user_id')
            );

            return response()->json([
                'success' => true,
                'data' => $voteStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }



    /**
     * Ottieni statistiche voti per una foto
     */
    public function getVoteStats(Request $request, int $entryId): JsonResponse
    {
        try {
            $userId = $request->input('user_id'); // Opzionale

            $stats = $this->voteService->getVoteStats($entryId, $userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Ottieni classifica foto per contest
     */
    public function getLeaderboard(Request $request, int $contestId): JsonResponse
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'between:1,50'],
            'order_by' => ['nullable', Rule::in(['vote_score', 'likes'])]
        ]);

        try {
            $topEntries = $this->voteService->getTopEntries(
                $contestId,
                $request->input('limit', 10),
                $request->input('order_by', 'vote_score')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'contest_id' => $contestId,
                    'entries' => $topEntries,
                    'order_by' => $request->input('order_by', 'vote_score'),
                    'limit' => $request->input('limit', 10)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
