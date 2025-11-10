<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\EmailTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Test
Route::get('/test', function () {
    return response()->json(['message' => 'API Goolliver attive!']);
});

// System Test Routes
Route::prefix('test')->group(function () {
    // Debug endpoint
    Route::get('/photo-debug', function () {
        try {
            $tests = [];

            // Test 1: Storage directories
            $tests['storage_directories'] = [
                'photos' => is_dir(storage_path('app/public/photos')),
                'thumbnails' => is_dir(storage_path('app/public/photos/thumbnails')),
                'temp' => is_dir(storage_path('app/temp'))
            ];

            // Test 2: Required classes
            $tests['required_classes'] = [
                'PhotoService' => class_exists('\App\Services\PhotoService'),
                'PhotoController' => class_exists('\App\Http\Controllers\Api\PhotoController'),
                'PhotoUploadRequest' => class_exists('\App\Http\Requests\PhotoUploadRequest'),
                'PhotoUploadException' => class_exists('\App\Exceptions\PhotoUploadException'),
                'EntryPolicy' => class_exists('\App\Policies\EntryPolicy')
            ];

            // Test 3: Database structure (questo potrebbe essere il problema)
            $tests['database_structure'] = [];
            try {
                $tests['database_structure']['entries_table'] = Schema::hasTable('entries');
                $tests['database_structure']['moderation_score_column'] = Schema::hasColumn('entries', 'moderation_score');
                $tests['database_structure']['processing_status_column'] = Schema::hasColumn('entries', 'processing_status');
                $tests['database_structure']['file_size_column'] = Schema::hasColumn('entries', 'file_size');
            } catch (\Exception $e) {
                $tests['database_structure']['error'] = $e->getMessage();
                $tests['database_structure']['trace'] = $e->getTraceAsString();
            }

            return response()->json([
                'status' => 'DEBUG_SUCCESS',
                'tests' => $tests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'DEBUG_ERROR',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });
    Route::get('/system-status', [App\Http\Controllers\Api\SystemTestController::class, 'systemStatus']);
    Route::get('/photo-system-test', [App\Http\Controllers\Api\SystemTestController::class, 'photoSystemTest']);
    Route::get('/security-check', [App\Http\Controllers\Api\SystemTestController::class, 'securityCheck']);

    // Photo Upload Test Routes
    Route::prefix('photos')->group(function () {
        Route::get('/page', [App\Http\Controllers\PhotoTestController::class, 'testPage']);
        Route::post('/upload', [App\Http\Controllers\PhotoTestController::class, 'testUpload']);
        Route::get('/storage-info', [App\Http\Controllers\PhotoTestController::class, 'storageInfo']);
        Route::get('/list', [App\Http\Controllers\PhotoTestController::class, 'listPhotos']);
    });
});

// 🔐 AUTENTICAZIONE WEB
// Route per reindirizzare alla pagina di login
Route::get('/login', function () {
    return redirect('/api/login-form');
})->name('login');

// Form di login visuale
Route::get('/login-form', [App\Http\Controllers\WebAuthController::class, 'showLoginForm']);
Route::middleware(['throttle:5,1'])->post('/web-login', [App\Http\Controllers\WebAuthController::class, 'webLogin']);
Route::get('/quick-login/{userId}', [App\Http\Controllers\WebAuthController::class, 'quickLogin']);

// Rotte Contest
Route::prefix('contests')->group(function () {
    Route::get('/', [ContestController::class, 'index']);          // lista concorsi
    Route::post('/', [ContestController::class, 'store']);         // crea concorso
    Route::get('/{id}', [ContestController::class, 'show']);       // singolo concorso
    Route::put('/{id}', [ContestController::class, 'update']);     // aggiorna concorso
    Route::delete('/{id}', [ContestController::class, 'destroy']); // elimina concorso
});

// Rotte Entry
Route::prefix('entries')->group(function () {
    Route::get('/', [EntryController::class, 'index']);          // lista di tutte le entries
    Route::post('/', [EntryController::class, 'store']);         // nuova entry (foto)
    Route::get('/{id}', [EntryController::class, 'show']);       // singola entry
    Route::put('/{id}', [EntryController::class, 'update']);     // modifica entry
    Route::delete('/{id}', [EntryController::class, 'destroy']); // elimina entry
});

// Rotte Votes
Route::prefix('votes')->group(function () {
    Route::get('/', [VoteController::class, 'index']);           // tutti i voti
    Route::post('/', [VoteController::class, 'store']);          // nuovo voto
    Route::get('/{id}', [VoteController::class, 'show']);        // singolo voto
    Route::delete('/{id}', [VoteController::class, 'destroy']);  // elimina voto (se serve)
});

// Rotte Transactions
Route::prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);           // tutte le transazioni
    Route::post('/', [TransactionController::class, 'store']);          // nuova transazione
    Route::get('/{id}', [TransactionController::class, 'show']);        // singola transazione
    Route::delete('/{id}', [TransactionController::class, 'destroy']);  // elimina transazione
});

// Rotte AuthController
Route::middleware(['throttle:3,1'])->post('/register', [AuthController::class, 'register']);
Route::middleware(['throttle:5,1'])->post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // 📸 ROTTE PHOTO UPLOAD E GESTIONE
    Route::prefix('photos')->group(function () {
        // Upload nuova foto
        Route::post('/upload', [PhotoController::class, 'upload'])
            ->middleware(['throttle:5,1']); // Max 5 upload al minuto

        // Gestione foto esistenti
        Route::put('/{entry}', [PhotoController::class, 'update'])
            ->middleware(['can:update,entry']);
        Route::delete('/{entry}', [PhotoController::class, 'destroy'])
            ->middleware(['can:delete,entry']);

        // Visualizzazione foto
        Route::get('/{entry}', [PhotoController::class, 'show']);

        // Gallery e listing
        Route::get('/contest/{contest}/gallery', [PhotoController::class, 'gallery']);
        Route::get('/user/my-photos', [PhotoController::class, 'userPhotos']);

        // Stato moderazione
        Route::get('/{entry}/moderation-status', [PhotoController::class, 'moderationStatus'])
            ->middleware(['can:view,entry']);

        // Upload progress (per future implementazioni)
        Route::get('/upload-progress', [PhotoController::class, 'uploadProgress']);
    });
});

// Rotte SocialAuthController
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// Rotte Email Testing (per sviluppo)
Route::prefix('email-test')->group(function () {
    Route::post('/welcome', [EmailTestController::class, 'sendWelcomeEmail']);
    Route::post('/new-contest', [EmailTestController::class, 'sendNewContestEmail']);
    Route::post('/reminder', [EmailTestController::class, 'sendContestReminderEmail']);
    Route::post('/test-all', [EmailTestController::class, 'sendTestEmails']);
});

// Rotte Email Preview (per visualizzare le email nel browser)
Route::prefix('email-preview')->group(function () {
    Route::get('/welcome', [App\Http\Controllers\EmailPreviewController::class, 'previewWelcome']);
    Route::get('/new-contest', [App\Http\Controllers\EmailPreviewController::class, 'previewNewContest']);
    Route::get('/reminder', [App\Http\Controllers\EmailPreviewController::class, 'previewReminder']);
});

// 🔒 NOTIFICHE WEB - Autenticazione con cookie (PRIMA del gruppo auth:sanctum)
Route::get('/notifications/view-test', function (Illuminate\Http\Request $request) {
    return response()->json([
        'message' => 'Route raggiunta con successo!',
        'cookies' => $request->cookies->all(),
        'token' => $request->cookie('auth_token')
    ]);
});

Route::get('/notifications/view', function (Illuminate\Http\Request $request) {
    try {
        // Debug completo per capire il problema
        $token = $request->cookie('auth_token');
        $allCookies = $request->cookies->all();

        // Se non c'è token, mostra debug completo
        if (!$token) {
            return response()->json([
                'error' => 'Token non trovato nei cookie',
                'debug' => [
                    'cookies_presenti' => array_keys($allCookies),
                    'tutti_i_cookies' => $allCookies,
                    'auth_token_specifico' => $request->cookie('auth_token'),
                    'headers' => $request->headers->all()
                ]
            ], 400);
        }

        // Verifica il token manualmente
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'error' => 'Token non valido',
                'debug' => [
                    'token_ricevuto' => substr($token, 0, 20) . '...',
                    'token_length' => strlen($token),
                    'database_check' => 'Token non trovato nel database'
                ]
            ], 400);
        }

        $user = $accessToken->tokenable;

        if (!$user) {
            return response()->json([
                'error' => 'Utente associato al token non trovato',
                'debug' => [
                    'access_token_id' => $accessToken->id,
                    'tokenable_type' => $accessToken->tokenable_type,
                    'tokenable_id' => $accessToken->tokenable_id
                ]
            ], 400);
        }

        // Ora generiamo le notifiche
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->get();
        $unreadCount = $user->unreadNotifications()->count();

        $html = "<!DOCTYPE html>
<html>
<head>
    <title>📱 Notifiche Goolliver - {$user->name}</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; color: #333; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; background: #e3f2fd; padding: 15px; border-radius: 8px; }
        .stat { text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #1976d2; }
        .notification { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; transition: transform 0.2s; }
        .notification:hover { transform: translateY(-2px); }
        .unread { border-left: 5px solid #ff4444; background: #fff8f8; }
        .read { border-left: 5px solid #44ff44; opacity: 0.7; }
        .title { font-weight: bold; margin-bottom: 8px; color: #333; }
        .message { margin: 10px 0; color: #666; }
        .meta { font-size: 0.9em; color: #999; }
        .user-info { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .refresh-btn { position: fixed; bottom: 30px; right: 30px; background: #667eea; color: white; border: none; border-radius: 50px; padding: 15px 20px; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .logout-btn { background: #ff6b6b; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>📱 Notifiche Goolliver</h1>
        </div>
        
        <div class='user-info'>
            <h2>👤 {$user->name}</h2>
            <p>📧 {$user->email}</p>
            <p>🔑 Autenticato con successo!</p>
            <button class='logout-btn' onclick='logout()'>🚪 Logout</button>
        </div>

        <div class='stats'>
            <div class='stat'>
                <div class='stat-number'>{$notifications->count()}</div>
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
        
        <div class='notifications'>";

        if ($notifications->count() > 0) {
            foreach ($notifications as $notification) {
                $class = $notification->read_at ? 'read' : 'unread';
                $status = $notification->read_at ? '✅ Letta' : '🔔 Non letta';

                $safeTitle = htmlspecialchars($notification->title, ENT_QUOTES, 'UTF-8');
                $safeMessage = htmlspecialchars($notification->message, ENT_QUOTES, 'UTF-8');

                $html .= "
                <div class='notification {$class}'>
                    <div class='title'>{$safeTitle}</div>
                    <div class='message'>{$safeMessage}</div>
                    <div class='meta'>
                        {$status} - Creata: {$notification->created_at->format('d/m/Y H:i')}
                    </div>
                </div>";
            }
        } else {
            $html .= "<div class='notification'><div class='title'>📭 Nessuna notifica</div><div class='message'>Non ci sono notifiche da mostrare al momento.</div></div>";
        }

        $html .= "
        </div>
    </div>
    
    <button class='refresh-btn' onclick='location.reload()'>🔄 Aggiorna</button>
    
    <script>
        function logout() {
            document.cookie = 'auth_token=; path=/; max-age=0';
            window.location.href = '/api/login-form';
        }
    </script>
</body>
</html>";

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Errore nel caricamento delle notifiche',
            'message' => $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'cookie_token' => $request->cookie('auth_token') ? 'presente' : 'mancante'
            ]
        ], 500);
    }
});

// Rotte Notifiche (richiedono autenticazione)
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [App\Http\Controllers\NotificationController::class, 'index']);                    // Lista notifiche
    Route::get('/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);  // Conta non lette

    // Route protette da ownership
    Route::middleware('resource.owner:notification')->group(function () {
        Route::get('/{id}', [App\Http\Controllers\NotificationController::class, 'show']);                 // Singola notifica
        Route::patch('/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);    // Segna come letta
        Route::patch('/{id}/unread', [App\Http\Controllers\NotificationController::class, 'markAsUnread']); // Segna come non letta
        Route::delete('/{id}', [App\Http\Controllers\NotificationController::class, 'destroy']);           // Elimina notifica
    });

    Route::patch('/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']); // Tutte lette (sicura)
    Route::delete('/read/all', [App\Http\Controllers\NotificationController::class, 'deleteRead']);    // Elimina tutte lette (sicura)
});

// Rotte Test Notifiche (per sviluppo)
Route::prefix('notification-test')->group(function () {
    Route::post('/welcome', [App\Http\Controllers\NotificationTestController::class, 'createWelcomeTest']);
    Route::post('/contest', [App\Http\Controllers\NotificationTestController::class, 'createContestTest']);
    Route::post('/reminder', [App\Http\Controllers\NotificationTestController::class, 'createReminderTest']);
    Route::post('/all', [App\Http\Controllers\NotificationTestController::class, 'createTestNotifications']);
    Route::post('/contest-automation', [App\Http\Controllers\NotificationTestController::class, 'testContestAutomation']);
});

// Debug route
Route::get('/debug/contest-creation', [App\Http\Controllers\DebugController::class, 'testContestCreation']);

// Route di debug per i cookie
Route::get('/debug/cookies', function (Illuminate\Http\Request $request) {
    return response()->json([
        'tutti_i_cookies' => $request->cookies->all(),
        'auth_token' => $request->cookie('auth_token'),
        'headers' => $request->headers->all(),
        'session_id' => session()->getId()
    ]);
});

// Route per generare e impostare manualmente un token
Route::get('/debug/set-token/{userId}', function (Illuminate\Http\Request $request, $userId) {
    $user = \App\Models\User::find($userId);
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $token = $user->createToken('debug-token')->plainTextToken;

    return response()->json([
        'message' => 'Token creato e impostato nei cookie',
        'user' => $user->name,
        'token_preview' => substr($token, 0, 20) . '...'
    ])->withCookie('auth_token', $token, 60 * 24, '/', null, false, false);
});

// Le route /notifications/view e /notifications/view-test sono ora prima del middleware auth:sanctum
