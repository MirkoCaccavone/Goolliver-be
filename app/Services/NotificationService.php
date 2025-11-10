<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Contest;

class NotificationService
{
    /**
     * Crea una notifica di benvenuto
     */
    public static function createWelcomeNotification(User $user): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => '🎉 Benvenuto in Goolliver!',
            'message' => "Ciao {$user->name}! Il tuo account è stato creato con successo. Inizia subito a partecipare ai nostri concorsi fotografici!",
            'type' => 'welcome',
            'data' => [
                'action' => 'view_contests',
                'icon' => '🎉',
                'priority' => 'high'
            ]
        ]);
    }

    /**
     * Crea una notifica per un nuovo concorso
     */
    public static function createNewContestNotification(User $user, Contest $contest): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => '📸 Nuovo Concorso Disponibile!',
            'message' => "È iniziato un nuovo concorso: \"{$contest->title}\". Partecipa ora e mostra il tuo talento fotografico!",
            'type' => 'contest',
            'data' => [
                'contest_id' => $contest->id,
                'contest_title' => $contest->title,
                'action' => 'view_contest',
                'url' => "/contests/{$contest->id}",
                'icon' => '📸',
                'priority' => 'high'
            ]
        ]);
    }

    /**
     * Crea una notifica di reminder per un concorso in scadenza
     */
    public static function createContestReminderNotification(User $user, Contest $contest): Notification
    {
        $daysLeft = now()->diffInDays($contest->end_date);
        $timeLeft = $daysLeft > 1 ? "{$daysLeft} giorni" : 'poche ore';

        return Notification::create([
            'user_id' => $user->id,
            'title' => '⏰ Concorso in Scadenza!',
            'message' => "Il concorso \"{$contest->title}\" scade tra {$timeLeft}. Non perdere l'occasione di partecipare!",
            'type' => 'reminder',
            'data' => [
                'contest_id' => $contest->id,
                'contest_title' => $contest->title,
                'end_date' => $contest->end_date->toISOString(),
                'days_left' => $daysLeft,
                'action' => 'join_contest',
                'url' => "/contests/{$contest->id}",
                'icon' => '⏰',
                'priority' => 'medium'
            ]
        ]);
    }

    /**
     * Crea una notifica per vincita concorso
     */
    public static function createWinnerNotification(User $user, Contest $contest, int $position): Notification
    {
        $titles = [
            1 => '🥇 Complimenti! Hai Vinto!',
            2 => '🥈 Secondo Posto!',
            3 => '🥉 Terzo Posto!'
        ];

        $messages = [
            1 => "Fantastico! Hai vinto il primo posto nel concorso \"{$contest->title}\"! 🎊",
            2 => "Ottimo lavoro! Hai conquistato il secondo posto nel concorso \"{$contest->title}\"! 👏",
            3 => "Bravo! Hai ottenuto il terzo posto nel concorso \"{$contest->title}\"! 🎉"
        ];

        return Notification::create([
            'user_id' => $user->id,
            'title' => $titles[$position] ?? '🏆 Congratulazioni!',
            'message' => $messages[$position] ?? "Complimenti per il tuo piazzamento nel concorso \"{$contest->title}\"!",
            'type' => 'success',
            'data' => [
                'contest_id' => $contest->id,
                'contest_title' => $contest->title,
                'position' => $position,
                'action' => 'view_results',
                'url' => "/contests/{$contest->id}/results",
                'icon' => ['🥇', '🥈', '🥉'][$position - 1] ?? '🏆',
                'priority' => 'high'
            ]
        ]);
    }

    /**
     * Crea una notifica per nuovo like ricevuto
     */
    public static function createLikeNotification(User $user, $photo, User $liker): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'title' => '❤️ Nuove Reazioni!',
            'message' => "{$liker->name} ha messo like alla tua foto! Continua così!",
            'type' => 'info',
            'data' => [
                'photo_id' => $photo->id,
                'liker_id' => $liker->id,
                'liker_name' => $liker->name,
                'action' => 'view_photo',
                'url' => "/photos/{$photo->id}",
                'icon' => '❤️',
                'priority' => 'low'
            ]
        ]);
    }

    /**
     * Crea una notifica generica
     */
    public static function createGenericNotification(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => array_merge([
                'icon' => '📢',
                'priority' => 'medium'
            ], $data)
        ]);
    }

    /**
     * Invia notifiche a tutti gli utenti
     */
    public static function notifyAllUsers(
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): int {
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            static::createGenericNotification($user, $title, $message, $type, $data);
            $count++;
        }

        return $count;
    }

    /**
     * Invia notifiche a utenti specifici
     */
    public static function notifyUsers(
        array $userIds,
        string $title,
        string $message,
        string $type = 'info',
        array $data = []
    ): int {
        $users = User::whereIn('id', $userIds)->get();
        $count = 0;

        foreach ($users as $user) {
            static::createGenericNotification($user, $title, $message, $type, $data);
            $count++;
        }

        return $count;
    }
}
