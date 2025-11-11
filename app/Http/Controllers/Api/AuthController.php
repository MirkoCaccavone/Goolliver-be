<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Registrazione
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'phone' => 'nullable|string|max:20',
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
                'success' => false,
                'message' => 'Credenziali non valide',
                'errors' => [
                    'email' => ['Email non trovata.']
                ]
            ], 401);
        }

        // Verifica se l'utente ha una password (non è un utente social)
        if (is_null($user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Account social',
                'errors' => [
                    'email' => ['Questo account è registrato tramite social login (' . ucfirst($user->provider) . '). Usa il login social.']
                ]
            ], 400);
        }

        // Verifica la password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenziali non valide',
                'errors' => [
                    'password' => ['Password non corretta.']
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
}
