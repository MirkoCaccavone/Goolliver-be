<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EntryController extends Controller
{
    /**
     * Restituisce tutte le entries pubbliche/approvate
     * GET /api/entries
     */
    public function index()
    {
        // Recupera tutte le entries pubbliche tramite scope 'public' sul model Entry
        $entries = Entry::public()->get();
        return response()->json($entries);
    }

    // Crea una nuova entry con upload dell'immagine
    public function store(Request $request)
    {
        /**
         * Crea una nuova entry con upload dell'immagine
         * POST /api/entries
         * Richiede: user_id, contest_id, photo/image
         */
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'contest_id' => 'required|integer|exists:contests,id',
            'caption' => 'nullable|string|max:255',
        ]);
        // Accetta sia 'photo' che 'image' come campo file
        $file = $request->file('photo') ?? $request->file('image');
        if (!$file) {
            // Se manca il file, restituisce errore 422
            return response()->json(['error' => 'File mancante (photo o image richiesto)'], 422);
        }
        // Salva l’immagine in storage/app/public/uploads
        $path = $file->store('uploads', 'public');

        // Stato moderazione e pagamento
        $moderation_status = $request->input('moderation_status', 'pending');
        $user = \App\Models\User::find($request->user_id);
        $payment_status = 'pending';
        $expires_at = null;
        $used_credits = 0;

        if ($user && $user->photo_credits >= 10) {
            // L'utente ha almeno 10 crediti: li usa per caricare gratis
            $user->decrement('photo_credits', 10);
            $used_credits = 10;
            $payment_status = 'completed';
        }

        // Imposta la scadenza solo se la entry è pending e moderata
        if ($payment_status === 'pending' && in_array($moderation_status, ['approved', 'pending', 'pending_review'])) {
            $expires_at = now()->addSeconds(2);
        }

        // Log di debug
        Log::info('[ENTRY] payment_status ricevuto:', ['payment_status' => $payment_status]);
        Log::info('[ENTRY] moderation_status ricevuto:', ['moderation_status' => $moderation_status]);
        Log::info('[ENTRY] expires_at calcolato:', ['expires_at' => $expires_at]);
        Log::info('[ENTRY] used_credits:', ['used_credits' => $used_credits]);

        // Crea la entry nel database
        $entry = Entry::create([
            'user_id' => $request->user_id,
            'contest_id' => $request->contest_id,
            'photo_url' => '/storage/' . $path,
            'caption' => $request->caption,
            'moderation_status' => $moderation_status,
            'payment_status' => $payment_status,
            'expires_at' => $expires_at,
        ]);

        // Risposta con la entry creata
        return response()->json([
            'message' => $used_credits ? 'Entry creata usando 10 crediti!' : 'Entry creata, completa il pagamento.',
            'data' => $entry,
            'used_credits' => $used_credits,
        ], 201);
    }

    // Mostra una singola entry (solo se pubblica/approvata)
    public function show($id)
    {
        /**
         * Mostra una singola entry pubblica/approvata
         * GET /api/entries/{id}
         */
        $entry = Entry::public()->findOrFail($id);
        return response()->json($entry);
    }

    // Aggiorna una entry (caption o status)
    public function update(Request $request, $id)
    {
        /**
         * Aggiorna una entry (caption o status)
         * PATCH /api/entries/{id}
         */
        $entry = Entry::findOrFail($id);

        $request->validate([
            'caption' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,approved,rejected',
        ]);

        // Aggiorna solo i campi caption e status
        $entry->update($request->only(['caption', 'status']));

        return response()->json([
            'message' => 'Entry aggiornata con successo!',
            'data' => $entry,
        ]);
    }

    // Elimina una entry + l’immagine associata
    public function destroy($id)
    {
        /**
         * Elimina una entry e l’immagine associata
         * DELETE /api/entries/{id}
         */
        $entry = Entry::findOrFail($id);

        // Elimina l'immagine dal filesystem se esiste
        if ($entry->image_path && Storage::disk('public')->exists($entry->image_path)) {
            Storage::disk('public')->delete($entry->image_path);
        }

        // Elimina la entry dal database
        $entry->delete();

        return response()->json(['message' => 'Entry eliminata con successo']);
    }

    // Restituisce l'ultima entry dell'utente per un contest, gestendo scadenza
    public function last(Request $request)
    {
        /**
         * Restituisce l'ultima entry dell'utente per un contest, gestendo la scadenza
         * GET /api/entries/last?user_id=...&contest_id=...
         * Se la entry è scaduta, viene eliminata e viene restituito expired=true
         */
        $userId = $request->user_id;
        $contestId = $request->contest_id;
        if (!$userId || !$contestId) {
            return response()->json(['error' => 'user_id e contest_id richiesti'], 400);
        }
        // Recupera l'ultima entry per utente e contest
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
        // Entry valida restituita
        return response()->json(['entry' => $entry]);
    }
}
