<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Lista tutte le notifiche dell'utente autenticato
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $type = $request->get('type'); // Filtra per tipo
        $unreadOnly = $request->get('unread_only', false); // Solo non lette

        $query = $user->notifications();

        // Filtra per tipo se specificato
        if ($type) {
            $query->where('type', $type);
        }

        // Filtra solo non lette se richiesto
        if ($unreadOnly) {
            $query->unread();
        }

        $notifications = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }

    /**
     * Conta le notifiche non lette
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    /**
     * Mostra una notifica specifica
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifica non trovata'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Segna una notifica come letta
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        Log::info('[NotificationController] markAsRead', [
            'user_id' => $user ? $user->id : null,
            'notification_id' => $id
        ]);

        $notification = $user->notifications()->find($id);
        Log::info('[NotificationController] markAsRead - found', [
            'notification' => $notification,
            'notification_id' => $id,
            'user_id' => $user ? $user->id : null
        ]);

        if (!$notification) {
            Log::error('[NotificationController] markAsRead - not found', [
                'notification_id' => $id,
                'user_id' => $user ? $user->id : null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Notifica non trovata'
            ], 404);
        }

        try {
            Log::info('[NotificationController] markAsRead - before update', [
                'notification_id' => $id,
                'read_at_before' => $notification->read_at
            ]);
            $notification->markAsRead();
            Log::info('[NotificationController] markAsRead - after update', [
                'notification_id' => $id,
                'read_at_after' => $notification->fresh()->read_at
            ]);
        } catch (\Exception $e) {
            Log::error('[NotificationController] markAsRead - exception', [
                'notification_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante update',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifica segnata come letta',
            'data' => $notification->fresh()
        ]);
    }

    /**
     * Segna tutte le notifiche come lette
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $user->unreadNotifications()->update([
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Segnate come lette {$count} notifiche",
            'marked_count' => $count
        ]);
    }

    /**
     * Segna una notifica come non letta
     */
    public function markAsUnread(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifica non trovata'
            ], 404);
        }

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notifica segnata come non letta',
            'data' => $notification->fresh()
        ]);
    }

    /**
     * Elimina una notifica
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        Log::info('[NotificationController] destroy', ['user_id' => $user ? $user->id : null, 'id' => $id]);

        $notification = $user->notifications()->find($id);
        Log::info('[NotificationController] destroy - found', ['notification' => $notification]);

        if (!$notification) {
            Log::error('[NotificationController] destroy - not found', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Notifica non trovata'
            ], 404);
        }

        $notification->delete();
        Log::info('[NotificationController] destroy - deleted', ['id' => $id]);

        return response()->json([
            'success' => true,
            'message' => 'Notifica eliminata'
        ]);
    }

    /**
     * Elimina tutte le notifiche lette
     */
    public function deleteRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $user->notifications()->read()->delete();

        return response()->json([
            'success' => true,
            'message' => "Eliminate {$count} notifiche lette",
            'deleted_count' => $count
        ]);
    }
}
