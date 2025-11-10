<?php

namespace App\Listeners;

use App\Events\ContestCreated;
use App\Mail\NewContestMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyUsersOfNewContest implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ContestCreated $event): void
    {
        $contest = $event->contest;

        try {
            // Ottieni tutti gli utenti attivi
            $users = User::all();

            $emailCount = 0;
            $notificationCount = 0;

            foreach ($users as $user) {
                try {
                    // 1. Invia EMAIL nuovo concorso
                    Mail::to($user->email)->send(new NewContestMail($user, $contest));
                    $emailCount++;

                    // 2. Crea NOTIFICA in-app nuovo concorso
                    NotificationService::createNewContestNotification($user, $contest);
                    $notificationCount++;
                } catch (\Exception $e) {
                    Log::error("Errore invio notifica nuovo contest per user {$user->id}: " . $e->getMessage());
                    // Continua con gli altri utenti anche se uno fallisce
                }
            }

            Log::info("Contest {$contest->id} notificato: {$emailCount} email, {$notificationCount} notifiche in-app inviate");
        } catch (\Exception $e) {
            Log::error("Errore generale notifica nuovo contest {$contest->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ContestCreated $event, \Throwable $exception): void
    {
        Log::error("Failed to notify users of new contest {$event->contest->id}: " . $exception->getMessage());
    }
}
