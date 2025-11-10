<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SimpleNotificationController extends Controller
{
    public function showNotifications(Request $request, $userId = null)
    {
        try {
            // 🔒 SICUREZZA: Usa sempre l'utente autenticato
            $user = $request->user();

            if (!$user) {
                return response('<h1>❌ Nessun utente trovato</h1>', 404);
            }

            $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();
            $unreadCount = $user->unreadNotifications()->count();

            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>📱 Notifiche Goolliver</title>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                    .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
                    .header { text-align: center; margin-bottom: 20px; color: #333; }
                    .stats { display: flex; justify-content: space-around; margin: 20px 0; }
                    .stat { text-align: center; padding: 10px; background: #e3f2fd; border-radius: 5px; }
                    .notification { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
                    .unread { border-left: 4px solid #ff4444; background: #fff8f8; }
                    .read { border-left: 4px solid #44ff44; opacity: 0.7; }
                    .title { font-weight: bold; margin-bottom: 5px; }
                    .message { margin: 10px 0; }
                    .meta { font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>📱 Notifiche Goolliver</h1>
                        <p>👤 Utente: {$user->name} ({$user->email})</p>
                    </div>
                    
                    <div class='stats'>
                        <div class='stat'>
                            <div><strong>" . $notifications->count() . "</strong></div>
                            <div>Totale</div>
                        </div>
                        <div class='stat'>
                            <div><strong>{$unreadCount}</strong></div>
                            <div>Non Lette</div>
                        </div>
                        <div class='stat'>
                            <div><strong>" . ($notifications->count() - $unreadCount) . "</strong></div>
                            <div>Lette</div>
                        </div>
                    </div>
                    
                    <div class='notifications'>";

            if ($notifications->count() > 0) {
                foreach ($notifications as $notification) {
                    $class = $notification->read_at ? 'read' : 'unread';
                    $status = $notification->read_at ? '✅ Letta' : '🔔 Non letta';

                    $html .= "
                        <div class='notification {$class}'>
                            <div class='title'>{$notification->title}</div>
                            <div class='message'>{$notification->message}</div>
                            <div class='meta'>
                                {$status} - Creata: " . $notification->created_at->format('d/m/Y H:i') . "
                            </div>
                        </div>";
                }
            } else {
                $html .= "<div class='notification'><p>📭 Nessuna notifica trovata</p></div>";
            }

            $html .= "
                    </div>
                    <div style='text-align: center; margin-top: 20px;'>
                        <button onclick='location.reload()' style='padding: 10px 20px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer;'>🔄 Aggiorna</button>
                    </div>
                </div>
            </body>
            </html>";

            return response($html)->header('Content-Type', 'text/html; charset=utf-8');
        } catch (\Exception $e) {
            return response("
                <h1>❌ Errore</h1>
                <p><strong>Messaggio:</strong> {$e->getMessage()}</p>
                <p><strong>File:</strong> {$e->getFile()}</p>
                <p><strong>Linea:</strong> {$e->getLine()}</p>
            ", 500)->header('Content-Type', 'text/html; charset=utf-8');
        }
    }
}
