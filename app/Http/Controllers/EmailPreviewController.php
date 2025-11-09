<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Mail\NewContestMail;
use App\Mail\ContestReminderMail;
use App\Models\User;
use App\Models\Contest;
use Illuminate\Http\Request;

class EmailPreviewController extends Controller
{
    /**
     * Preview welcome email
     */
    public function previewWelcome()
    {
        $user = User::first() ?? new User([
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com'
        ]);

        $mail = new WelcomeMail($user);
        return $mail->render();
    }

    /**
     * Preview new contest email
     */
    public function previewNewContest()
    {
        $user = User::first() ?? new User([
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com'
        ]);

        $contest = Contest::first() ?? new Contest([
            'title' => 'Concorso Foto Natale 2025',
            'description' => 'Condividi la tua foto natalizia più bella e vinci fantastici premi!',
            'status' => 'open',
            'max_participants' => 100,
            'prize' => 'iPhone 15 Pro',
            'created_at' => now()
        ]);

        $mail = new NewContestMail($user, $contest);
        return $mail->render();
    }

    /**
     * Preview contest reminder email
     */
    public function previewReminder()
    {
        $user = User::first() ?? new User([
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com'
        ]);

        $contest = Contest::first() ?? new Contest([
            'title' => 'Concorso Foto Natale 2025',
            'description' => 'Condividi la tua foto natalizia più bella e vinci fantastici premi!',
            'status' => 'open',
            'max_participants' => 100,
            'prize' => 'iPhone 15 Pro',
            'participants' => 78, // 78% pieno per mostrare l'urgenza
            'created_at' => now()->subDays(3)
        ]);

        $mail = new ContestReminderMail($user, $contest);
        return $mail->render();
    }
}
