<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vote;

class VoteController extends Controller
{
    // Classifica delle entry per numero di like in un contest
    public function getLeaderboard($contestId)
    {
        $entries = \App\Models\Entry::where('contest_id', $contestId)
            ->where('moderation_status', 'approved')
            ->orderByDesc('likes_count')
            ->with(['user'])
            ->get();

        return response()->json([
            'leaderboard' => $entries
        ]);
    }
    // Lista di tutti i voti
    public function index()
    {
        $votes = Vote::with(['entry', 'user'])->get();
        return response()->json($votes);
    }

    // Crea un nuovo voto
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'entry_id' => 'required|integer|exists:entries,id',
        ]);

        $userId = $request->user_id;
        $entryId = $request->entry_id;

        $entry = \App\Models\Entry::findOrFail($entryId);
        $contest = $entry->contest;

        // 1. Permetti il voto solo se il contest è in stato voting
        if ($contest->status !== 'voting') {
            return response()->json(['message' => 'La votazione non è attiva per questo contest'], 403);
        }

        // 2. Permetti il voto solo se l'utente ha partecipato (almeno una entry approvata in questo contest)
        $userHasEntry = $contest->entries()
            ->where('user_id', $userId)
            ->where('moderation_status', 'approved')
            ->exists();
        if (!$userHasEntry) {
            return response()->json(['message' => 'Solo chi ha partecipato può votare'], 403);
        }

        // 3. Impedisci di votare la propria foto
        if ($entry->user_id == $userId) {
            return response()->json(['message' => 'Non puoi votare la tua foto'], 403);
        }

        // 4. Impedisci di votare più volte la stessa entry
        $voteExists = \App\Models\Vote::where('user_id', $userId)
            ->where('entry_id', $entryId)
            ->exists();
        if ($voteExists) {
            return response()->json(['message' => 'Hai già votato questa foto'], 400);
        }

        $vote = \App\Models\Vote::create([
            'user_id' => $userId,
            'entry_id' => $entryId,
            'vote_type' => \App\Models\Vote::TYPE_LIKE,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        // Incrementa likes_count sulla entry
        $entry->increment('likes_count');

        return response()->json($vote, 201);
    }

    // Mostra un voto
    public function show($id)
    {
        $vote = Vote::with(['entry', 'user'])->findOrFail($id);
        return response()->json($vote);
    }

    // Elimina un voto (se necessario)
    public function destroy($id)
    {
        $vote = Vote::findOrFail($id);
        $vote->delete();
        return response()->json(['message' => 'Voto eliminato']);
    }
}
