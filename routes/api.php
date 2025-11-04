<?php

use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\VoteController;
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
