<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Registrazione
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|min:2|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
            ], [
                'name.required' => 'Il nome è obbligatorio',
                'name.min' => 'Il nome deve avere almeno 2 caratteri',
                'name.max' => 'Il nome non può superare i 255 caratteri',
                'email.required' => 'L\'email è obbligatoria',
                'email.email' => 'Inserisci un indirizzo email valido',
                'email.unique' => 'Questa email è già registrata. Prova ad accedere o usa un\'email diversa',
                'password.required' => 'La password è obbligatoria',
                'password.min' => 'La password deve avere almeno 8 caratteri',
                'password.confirmed' => 'Le due password non coincidono',
                'phone.max' => 'Il numero di telefono non può superare i 20 caratteri'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dati di registrazione non validi',
                'errors' => $e->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // 🚀 Lancia l'evento per inviare email + notifica automaticamente!
        \App\Events\UserRegistered::dispatch($user);

        return response()->json([
            'message' => 'Registrazione avvenuta con successo',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'L\'email è obbligatoria',
                'email.email' => 'Inserisci un indirizzo email valido',
                'password.required' => 'La password è obbligatoria'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dati mancanti o non validi',
                'errors' => $e->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Verifica se l'utente esiste
        if (!$user) {
            return response()->json([
                'message' => 'Email o password non corretti',
                'errors' => [
                    'email' => ['Email non trovata']
                ]
            ], 401);
        }

        // Verifica se l'utente ha una password (non è un utente social)
        if (is_null($user->password)) {
            return response()->json([
                'message' => 'Questo account è registrato tramite social login (' . ucfirst($user->provider) . '). Usa il login social.',
                'errors' => [
                    'email' => ['Account social - usa ' . ucfirst($user->provider)]
                ]
            ], 400);
        }

        // Verifica la password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email o password non corretti',
                'errors' => [
                    'password' => ['Password non corretta']
                ]
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Aggiorna last_login_at
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Login effettuato con successo',
            'token' => $token,
            'user' => $user->fresh(), // Ricarica l'utente con i dati aggiornati
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout effettuato con successo'
        ]);
    }

    // Richiesta reset password
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'L\'email è obbligatoria',
                'email.email' => 'Inserisci un indirizzo email valido',
                'email.exists' => 'Non abbiamo trovato nessun account con questa email'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Email non valida o non trovata',
                'errors' => $e->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Verifica se è un utente social
        if (is_null($user->password)) {
            return response()->json([
                'message' => 'Account social registrato con ' . ucfirst($user->provider) . '. Non è possibile reimpostare la password.',
                'errors' => [
                    'email' => ['Questo account usa ' . ucfirst($user->provider) . ' per accedere']
                ]
            ], 400);
        }

        // Genera un token di reset (per ora simuliamo)
        $resetToken = Str::random(64);

        // In una implementazione reale, salveresti il token in DB e invieresti email
        // Per ora ritorniamo solo un messaggio di successo

        return response()->json([
            'message' => 'Richiesta ricevuta correttamente! (NOTA: Sistema email non ancora configurato - usa il link di test qui sotto)',
            'reset_token' => $resetToken, // Solo per testing, in produzione non includere
            'test_reset_url' => "http://localhost:5173/reset-password?email=" . urlencode($request->email) . "&token=" . $resetToken
        ]);
    }

    // Reset password (con token)
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'token' => 'required|string|min:10',
                'password' => 'required|min:8|confirmed',
            ], [
                'email.required' => 'L\'email è obbligatoria',
                'email.email' => 'Email non valida',
                'email.exists' => 'Email non trovata',
                'token.required' => 'Token di reset richiesto',
                'token.min' => 'Token non valido',
                'password.required' => 'La nuova password è obbligatoria',
                'password.min' => 'La password deve avere almeno 8 caratteri',
                'password.confirmed' => 'Le password non coincidono'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dati non validi per il reset della password',
                'errors' => $e->errors()
            ], 422);
        }

        // In una implementazione reale verificheresti il token dal DB
        // Per ora simuliamo che sia sempre valido se è lungo almeno 10 caratteri

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password reimpostata con successo. Ora puoi accedere con la nuova password.'
        ]);
    }
}
