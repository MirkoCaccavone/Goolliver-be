<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EntryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Tutti possono vedere le entry pubbliche
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Entry $entry): bool
    {
        // Tutti possono vedere entry approvate, solo il proprietario può vedere le proprie
        return $entry->moderation_status === 'approved' || $entry->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Utenti autenticati possono creare entry
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Entry $entry): bool
    {
        // Solo il proprietario può modificare la propria entry
        // E solo se il contest è ancora attivo
        return $entry->user_id === $user->id && $entry->contest->isActive();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Entry $entry): bool
    {
        // Solo il proprietario può eliminare la propria entry
        // E solo se il contest è ancora attivo
        return $entry->user_id === $user->id && $entry->contest->isActive();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Entry $entry): bool
    {
        // Solo admin o proprietario possono ripristinare
        return $user->is_admin || $entry->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Entry $entry): bool
    {
        // Solo admin possono eliminare definitivamente
        return $user->is_admin ?? false;
    }

    /**
     * Determine whether the user can view moderation details
     */
    public function viewModerationDetails(User $user, Entry $entry): bool
    {
        // Solo il proprietario può vedere i dettagli della moderazione
        return $entry->user_id === $user->id;
    }
}
