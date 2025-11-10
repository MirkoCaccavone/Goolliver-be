<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotifications implements ShouldQueue
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
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        try {
            // 1. Invia EMAIL di benvenuto
            Mail::to($user->email)->send(new WelcomeMail($user));
            Log::info("Email di benvenuto inviata a: {$user->email}");

            // 2. Crea NOTIFICA in-app di benvenuto
            NotificationService::createWelcomeNotification($user);
            Log::info("Notifica in-app di benvenuto creata per user ID: {$user->id}");
        } catch (\Exception $e) {
            Log::error("Errore invio benvenuto per user {$user->id}: " . $e->getMessage());

            // Re-throw per far fallire il job se necessario
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(UserRegistered $event, \Throwable $exception): void
    {
        Log::error("Failed to send welcome notifications for user {$event->user->id}: " . $exception->getMessage());
    }
}
