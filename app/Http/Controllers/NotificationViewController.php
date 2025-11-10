<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class NotificationViewController extends Controller
{
    /**
     * Mostra le notifiche di un utente nel browser
     */
    public function showNotifications(Request $request, $userId = null)
    {
        // Se non specifichi un utente, usa il primo disponibile
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            return response('<h1>❌ Nessun utente trovato</h1>', 404);
        }

        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();
        $unreadCount = $user->unreadNotifications()->count();

        $html = $this->generateNotificationHtml($user, $notifications, $unreadCount);

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function generateNotificationHtml($user, $notifications, $unreadCount)
    {
        $notificationHtml = '';

        foreach ($notifications as $notification) {
            $isRead = $notification->read_at ? 'read' : 'unread';
            $readBadge = $notification->read_at ? '✅ Letta' : '🔔 Non letta';
            $readTime = $notification->read_at ? ' - Letta: ' . $notification->read_at->format('d/m/Y H:i') : '';

            $notificationHtml .= "
                <div class='notification {$isRead}'>
                    <div class='notification-header'>
                        <h3>{$notification->title}</h3>
                        <span class='badge {$isRead}'>{$readBadge}</span>
                    </div>
                    <p class='message'>{$notification->message}</p>
                    <div class='meta'>
                        <small>
                            📅 Creata: {$notification->created_at->format('d/m/Y H:i')}
                            {$readTime}
                        </small>
                        <br>
                        <small>🏷️ Tipo: {$notification->type}</small>
                    </div>
                    <div class='actions'>
                        <a href='/api/notifications/{$notification->id}/read' class='btn-read'>Segna come letta</a>
                        <a href='/api/notifications/{$notification->id}' class='btn-delete'>Elimina</a>
                    </div>
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>📱 Notifiche Goolliver - {$user->name}</title>
            <meta charset='utf-8'>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 15px;
                    padding: 30px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #f0f0f0;
                }
                .header h1 {
                    color: #333;
                    margin: 0;
                    font-size: 2.5em;
                }
                .user-info {
                    background: linear-gradient(45deg, #ff6b6b, #feca57);
                    color: white;
                    padding: 15px;
                    border-radius: 10px;
                    margin-bottom: 25px;
                    text-align: center;
                }
                .stats {
                    display: flex;
                    justify-content: space-around;
                    margin-bottom: 25px;
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 10px;
                }
                .stat {
                    text-align: center;
                }
                .stat-number {
                    font-size: 2em;
                    font-weight: bold;
                    color: #667eea;
                }
                .notification {
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    transition: transform 0.2s;
                }
                .notification:hover {
                    transform: translateY(-2px);
                }
                .notification.unread {
                    border-left: 5px solid #ff6b6b;
                    background: #fff8f8;
                }
                .notification.read {
                    border-left: 5px solid #51cf66;
                    opacity: 0.7;
                }
                .notification-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }
                .notification-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 1.2em;
                }
                .badge {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.8em;
                    font-weight: bold;
                }
                .badge.unread {
                    background: #ff6b6b;
                    color: white;
                }
                .badge.read {
                    background: #51cf66;
                    color: white;
                }
                .message {
                    color: #666;
                    line-height: 1.5;
                    margin: 15px 0;
                }
                .meta {
                    color: #999;
                    font-size: 0.9em;
                    margin: 15px 0;
                }
                .actions {
                    margin-top: 15px;
                }
                .actions a {
                    display: inline-block;
                    padding: 8px 15px;
                    margin-right: 10px;
                    text-decoration: none;
                    border-radius: 5px;
                    font-size: 0.9em;
                    transition: background 0.2s;
                }
                .btn-read {
                    background: #51cf66;
                    color: white;
                }
                .btn-delete {
                    background: #ff6b6b;
                    color: white;
                }
                .actions a:hover {
                    opacity: 0.8;
                }
                .empty {
                    text-align: center;
                    padding: 50px;
                    color: #999;
                }
                .refresh-btn {
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    background: #667eea;
                    color: white;
                    border: none;
                    border-radius: 50px;
                    padding: 15px 20px;
                    cursor: pointer;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                    font-size: 16px;
                }
                .refresh-btn:hover {
                    background: #5a67d8;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📱 Notifiche Goolliver</h1>
                </div>
                
                <div class='user-info'>
                    <h2>👤 Utente: {$user->name}</h2>
                    <p>📧 {$user->email}</p>
                </div>

                <div class='stats'>
                    <div class='stat'>
                        <div class='stat-number'>" . $notifications->count() . "</div>
                        <div>Totale Notifiche</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number'>{$unreadCount}</div>
                        <div>Non Lette</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number'>" . ($notifications->count() - $unreadCount) . "</div>
                        <div>Lette</div>
                    </div>
                </div>

                <div class='notifications'>
                    " . ($notifications->count() > 0 ? $notificationHtml : "<div class='empty'>📭 Nessuna notifica trovata</div>") . "
                </div>
            </div>
            
            <button class='refresh-btn' onclick='location.reload()'>🔄 Aggiorna</button>
        </body>
        </html>
        ";
    }
}
