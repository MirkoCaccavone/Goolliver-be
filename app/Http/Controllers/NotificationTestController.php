<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Contest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTestController extends Controller
{
    /**
     * Crea una notifica di benvenuto per test
     */
    public function createWelcomeTest(Request $request): JsonResponse
    {
        // Trova un utente o usa l'utente autenticato
        $user = $request->user() ?: User::first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun utente trovato'
            ], 404);
        }

        $notification = NotificationService::createWelcomeNotification($user);

        return response()->json([
            'success' => true,
            'message' => 'Notifica di benvenuto creata!',
            'data' => $notification
        ]);
    }

    /**
     * Crea una notifica nuovo concorso per test
     */
    public function createContestTest(Request $request): JsonResponse
    {
        // Trova un utente o usa l'utente autenticato
        $user = $request->user() ?: User::first();
        $contest = Contest::first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun utente trovato'
            ], 404);
        }

        if (!$contest) {
            // Crea un contest di esempio se non esiste
            $contest = Contest::create([
                'title' => 'Concorso Test Fotografico',
                'description' => 'Un concorso di esempio per testare le notifiche',
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'status' => 'active',
                'max_entries' => 100
            ]);
        }

        $notification = NotificationService::createNewContestNotification($user, $contest);

        return response()->json([
            'success' => true,
            'message' => 'Notifica nuovo concorso creata!',
            'data' => $notification
        ]);
    }

    /**
     * Crea una notifica reminder per test
     */
    public function createReminderTest(Request $request): JsonResponse
    {
        $user = $request->user() ?: User::first();
        $contest = Contest::first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun utente trovato'
            ], 404);
        }

        if (!$contest) {
            // Crea un contest in scadenza
            $contest = Contest::create([
                'title' => 'Concorso in Scadenza',
                'description' => 'Questo concorso scade presto!',
                'start_date' => now()->subDays(25),
                'end_date' => now()->addDays(2), // Scade tra 2 giorni
                'status' => 'active',
                'max_entries' => 100
            ]);
        }

        $notification = NotificationService::createContestReminderNotification($user, $contest);

        return response()->json([
            'success' => true,
            'message' => 'Notifica reminder creata!',
            'data' => $notification
        ]);
    }

    /**
     * Crea diverse notifiche per test completo
     */
    public function createTestNotifications(Request $request): JsonResponse
    {
        $user = $request->user() ?: User::first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun utente trovato'
            ], 404);
        }

        $notifications = [];

        // Notifica benvenuto
        $notifications[] = NotificationService::createWelcomeNotification($user);

        // Notifica generica
        $notifications[] = NotificationService::createGenericNotification(
            $user,
            '📱 Aggiornamento App',
            'È disponibile una nuova versione di Goolliver con fantastiche funzionalità!',
            'info',
            ['version' => '1.2.0', 'action' => 'update_app']
        );

        // Notifica vincita (simulata)
        $contest = Contest::first();
        if ($contest) {
            $notifications[] = NotificationService::createWinnerNotification($user, $contest, 1);
        }

        // Notifica like (simulata)
        $notifications[] = NotificationService::createGenericNotification(
            $user,
            '❤️ Nuove Reazioni!',
            'La tua foto ha ricevuto 5 nuovi like! Continua così!',
            'info',
            ['likes_count' => 5, 'action' => 'view_photo', 'icon' => '❤️']
        );

        return response()->json([
            'success' => true,
            'message' => 'Create ' . count($notifications) . ' notifiche di test!',
            'data' => $notifications
        ]);
    }

    /**
     * Testa l'automazione completa creando un contest
     */
    public function testContestAutomation(Request $request): JsonResponse
    {
        try {
            // Crea un contest di test
            $contest = Contest::create([
                'title' => 'Contest Automazione Test',
                'description' => 'Un contest per testare l\'automazione di email e notifiche',
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'status' => 'active',
                'max_entries' => 100
            ]);

            // Lancia l'evento per l'automazione
            \App\Events\ContestCreated::dispatch($contest);

            return response()->json([
                'success' => true,
                'message' => 'Contest creato e evento lanciato per automazione!',
                'contest' => $contest,
                'note' => 'Controlla i logs per verificare invio email e creazione notifiche automatiche'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione del contest: ' . $e->getMessage()
            ], 500);
        }
    }
}
