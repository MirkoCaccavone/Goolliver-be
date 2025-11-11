<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ModeratorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Controlla se l'utente è autenticato
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Accesso non autorizzato'
            ], 401);
        }

        $user = Auth::user();

        // Controlla se l'account è attivo
        if (!$user->is_active) {
            return response()->json([
                'error' => 'Account disabilitato'
            ], 403);
        }

        // Controlla se ha il ruolo moderatore o admin
        if (!in_array($user->role, ['moderator', 'admin'])) {
            return response()->json([
                'error' => 'Accesso riservato ai moderatori e amministratori'
            ], 403);
        }

        return $next($request);
    }
}
