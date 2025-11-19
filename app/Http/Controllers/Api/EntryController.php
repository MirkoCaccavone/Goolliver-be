<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EntryController extends Controller
{
    // Tutte le entries (solo quelle pubbliche/approvate)
    public function index()
    {
        $entries = Entry::public()->get();
        return response()->json($entries);
    }

    // Crea una nuova entry con upload dell'immagine
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'contest_id' => 'required|integer|exists:contests,id',
            'caption' => 'nullable|string|max:255',
        ]);
        // Accetta sia 'photo' che 'image' come campo file
        $file = $request->file('photo') ?? $request->file('image');
        if (!$file) {
            return response()->json(['error' => 'File mancante (photo o image richiesto)'], 422);
        }
        // Salva l’immagine in storage/app/public/uploads
        $path = $file->store('uploads', 'public');

        $moderation_status = $request->input('moderation_status', 'pending');
        $payment_status = $request->input('payment_status', 'pending');
        $expires_at = null;
        if ($payment_status === 'pending' && in_array($moderation_status, ['approved', 'pending', 'pending_review'])) {
            $expires_at = now()->addSeconds(2);
        }
        Log::info('[ENTRY] payment_status ricevuto:', ['payment_status' => $payment_status]);
        Log::info('[ENTRY] moderation_status ricevuto:', ['moderation_status' => $moderation_status]);
        Log::info('[ENTRY] expires_at calcolato:', ['expires_at' => $expires_at]);
        $entry = Entry::create([
            'user_id' => $request->user_id,
            'contest_id' => $request->contest_id,
            'photo_url' => '/storage/' . $path,
            'caption' => $request->caption,
            'moderation_status' => $moderation_status,
            'payment_status' => $payment_status,
            'expires_at' => $expires_at,
        ]);

        return response()->json([
            'message' => 'Entry creata con successo!',
            'data' => $entry,
        ], 201);
    }

    // Mostra una singola entry (solo se pubblica/approvata)
    public function show($id)
    {
        $entry = Entry::public()->findOrFail($id);
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

    // Restituisce l'ultima entry dell'utente per un contest, gestendo scadenza
    public function last(Request $request)
    {
        $userId = $request->user_id;
        $contestId = $request->contest_id;
        if (!$userId || !$contestId) {
            return response()->json(['error' => 'user_id e contest_id richiesti'], 400);
        }
        $entry = Entry::where('user_id', $userId)
            ->where('contest_id', $contestId)
            ->orderByDesc('created_at')
            ->first();
        if (!$entry) {
            return response()->json(['entry' => null]);
        }
        // Se payment pending e expires_at scaduto, cancella entry e segnala scadenza
        Log::info('[ENTRY-LAST] payment_status:', ['payment_status' => $entry->payment_status]);
        Log::info('[ENTRY-LAST] expires_at:', ['expires_at' => $entry->expires_at]);
        Log::info('[ENTRY-LAST] now:', ['now' => now()]);
        $isExpired = $entry->payment_status === 'pending' && $entry->expires_at && now()->greaterThan($entry->expires_at);
        Log::info('[ENTRY-LAST] isExpired:', ['isExpired' => $isExpired]);
        if ($isExpired) {
            $entry->delete();
            return response()->json([
                'expired' => true,
                'message' => 'Your upload expired. Please upload a new photo.'
            ]);
        }
        return response()->json(['entry' => $entry]);
    }
}
