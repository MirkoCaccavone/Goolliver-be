<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\EmailTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Test
Route::get('/test', function () {
    return response()->json(['message' => 'API Goolliver attive!']);
});

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
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
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
