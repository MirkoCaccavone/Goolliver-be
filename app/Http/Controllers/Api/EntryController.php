<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entry;
use Illuminate\Support\Facades\Storage;

class EntryController extends Controller
{
    // Tutte le entries
    public function index()
    {
        $entries = Entry::all();
        return response()->json($entries);
    }

    // Crea una nuova entry con upload dell'immagine
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'contest_id' => 'required|integer|exists:contests,id',
            'image' => 'required|file|image|max:2048', // max 2MB
            'caption' => 'nullable|string|max:255',
        ]);

        // Salva l’immagine in storage/app/public/uploads
        $path = $request->file('image')->store('uploads', 'public');

        // Crea l'entry nel DB
        $entry = Entry::create([
            'user_id' => $request->user_id,
            'contest_id' => $request->contest_id,
            // 'image_path' => $path,
            'image_url' => '/storage/' . $path,
            'description' => $request->caption,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Entry creata con successo!',
            'data' => $entry,
        ], 201);
    }

    // Mostra una singola entry
    public function show($id)
    {
        $entry = Entry::findOrFail($id);
        return response()->json($entry);
    }

    // Aggiorna una entry (caption o status)
    public function update(Request $request, $id)
    {
        $entry = Entry::findOrFail($id);

        $request->validate([
            'caption' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        $entry->update($request->only(['caption', 'status']));

        return response()->json([
            'message' => 'Entry aggiornata con successo!',
            'data' => $entry,
        ]);
    }

    // Elimina una entry + l’immagine associata
    public function destroy($id)
    {
        $entry = Entry::findOrFail($id);

        if ($entry->image_path && Storage::disk('public')->exists($entry->image_path)) {
            Storage::disk('public')->delete($entry->image_path);
        }

        $entry->delete();

        return response()->json(['message' => 'Entry eliminata con successo']);
    }
}
