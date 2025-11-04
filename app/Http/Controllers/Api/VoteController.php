<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vote;

class VoteController extends Controller
{
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

        // Verifica che l’utente non voti se stesso
        $voteExists = Vote::where('user_id', $request->user_id)
            ->where('entry_id', $request->entry_id)
            ->exists();

        if ($voteExists) {
            return response()->json(['message' => 'Hai già votato questa foto'], 400);
        }

        $vote = Vote::create([
            'user_id' => $request->user_id,
            'entry_id' => $request->entry_id,
        ]);

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
