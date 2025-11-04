<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entry;

class EntryController extends Controller
{
    // Tutte le entries
    public function index()
    {
        $entries = Entry::all();
        return response()->json($entries);
    }

    // Crea una nuova entry (foto partecipante)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'contest_id' => 'required|integer|exists:contests,id',
            'image_url' => 'required|string',
            'caption' => 'nullable|string|max:255',
        ]);

        $entry = Entry::create($request->all());
        return response()->json($entry, 201);
    }

    // Mostra una singola entry
    public function show($id)
    {
        $entry = Entry::findOrFail($id);
        return response()->json($entry);
    }

    // Aggiorna una entry
    public function update(Request $request, $id)
    {
        $entry = Entry::findOrFail($id);
        $entry->update($request->all());
        return response()->json($entry);
    }

    // Elimina una entry
    public function destroy($id)
    {
        $entry = Entry::findOrFail($id);
        $entry->delete();
        return response()->json(['message' => 'Entry eliminata']);
    }
}
