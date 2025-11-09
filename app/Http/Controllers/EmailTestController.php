<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Mail\NewContestMail;
use App\Mail\ContestReminderMail;
use App\Models\User;
use App\Models\Contest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;

class EmailTestController extends Controller
{
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::find($request->user_id);

        try {
            Mail::to($user->email)->send(new WelcomeMail($user));

            return response()->json([
                'success' => true,
                'message' => 'Welcome email sent successfully to ' . $user->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send new contest email
     */
    public function sendNewContestEmail(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'contest_id' => 'required|exists:contests,id'
        ]);

        $user = User::find($request->user_id);
        $contest = Contest::find($request->contest_id);

        try {
            Mail::to($user->email)->send(new NewContestMail($user, $contest));

            return response()->json([
                'success' => true,
                'message' => 'New contest email sent successfully to ' . $user->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send contest reminder email
     */
    public function sendContestReminderEmail(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'contest_id' => 'required|exists:contests,id'
        ]);

        $user = User::find($request->user_id);
        $contest = Contest::find($request->contest_id);

        try {
            Mail::to($user->email)->send(new ContestReminderMail($user, $contest));

            return response()->json([
                'success' => true,
                'message' => 'Contest reminder email sent successfully to ' . $user->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send test emails to all users
     */
    public function sendTestEmails(): JsonResponse
    {
        try {
            $users = User::limit(5)->get(); // Test with first 5 users
            $emailsSent = 0;

            foreach ($users as $user) {
                Mail::to($user->email)->send(new WelcomeMail($user));
                $emailsSent++;
            }

            return response()->json([
                'success' => true,
                'message' => "Test welcome emails sent to {$emailsSent} users"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test emails: ' . $e->getMessage()
            ], 500);
        }
    }
}
