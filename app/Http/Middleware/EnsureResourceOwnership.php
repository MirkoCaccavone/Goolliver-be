<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\Entry;
use App\Models\Vote;
use App\Models\Transaction;

class EnsureResourceOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $model
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $model = 'notification')
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $resourceId = $request->route('id');

        if (!$resourceId) {
            return $next($request);
        }

        $resource = $this->findResource($model, $resourceId);

        if (!$resource) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        // Verifica ownership
        if (!$this->userOwnsResource($user, $resource, $model)) {
            return response()->json([
                'error' => 'Forbidden: You do not have access to this resource'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Trova la risorsa in base al modello
     */
    private function findResource(string $model, int $resourceId)
    {
        switch ($model) {
            case 'notification':
                return Notification::find($resourceId);
            case 'entry':
                return Entry::find($resourceId);
            case 'vote':
                return Vote::find($resourceId);
            case 'transaction':
                return Transaction::find($resourceId);
            default:
                return null;
        }
    }

    /**
     * Verifica se l'utente possiede la risorsa
     */
    private function userOwnsResource($user, $resource, string $model): bool
    {
        switch ($model) {
            case 'notification':
            case 'entry':
            case 'vote':
            case 'transaction':
                return $resource->user_id === $user->id;
            default:
                return false;
        }
    }
}
