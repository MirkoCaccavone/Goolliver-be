<?php
// Test diretto per il nuovo sistema di voto
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🧪 Test Sistema di Voto Semplificato</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

try {
    $voteService = app(\App\Services\VoteService::class);

    echo "<div class='info'>";
    echo "<h3>📊 Stato Iniziale</h3>";

    // Mostra le entries esistenti
    $entries = \App\Models\Entry::take(3)->get();
    echo "<ul>";
    foreach ($entries as $entry) {
        echo "<li>Entry ID {$entry->id}: {$entry->title} - User: {$entry->user_id} - Contest: {$entry->contest_id} - Likes: {$entry->likes_count}</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Test 1: User 1 prova a votare Entry 20 (sua foto)
    echo "<div class='error'>";
    echo "<h3>❌ Test 1: User 1 vota la sua foto (ID 20)</h3>";
    try {
        $result = $voteService->toggleLike(20, 1, '127.0.0.1', 'Test');
        echo "<p>❌ ERRORE: Il voto dovrebbe essere bloccato!</p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    } catch (Exception $e) {
        echo "<p>✅ CORRETTO: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // Test 2: User 2 vota Entry 20 (foto di User 1)
    echo "<div class='success'>";
    echo "<h3>✅ Test 2: User 2 vota Entry 20</h3>";
    try {
        $result = $voteService->toggleLike(20, 2, '127.0.0.1', 'Test');
        echo "<p>✅ SUCCESSO: " . ($result['message'] ?? $result['action']) . "</p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    } catch (Exception $e) {
        echo "<p>❌ ERRORE: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // Test 3: User 2 prova a votare un'altra foto (dovrebbe spostare il voto)
    echo "<div class='info'>";
    echo "<h3>🔄 Test 3: User 2 sposta il voto su Entry 21</h3>";
    try {
        $result = $voteService->toggleLike(21, 2, '127.0.0.1', 'Test');
        echo "<p>✅ RISULTATO: " . ($result['message'] ?? $result['action']) . "</p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    } catch (Exception $e) {
        echo "<p>❌ ERRORE: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    // Test 4: Stato finale
    echo "<div class='info'>";
    echo "<h3>📊 Stato Finale</h3>";
    $entries = \App\Models\Entry::take(3)->get();
    echo "<ul>";
    foreach ($entries as $entry) {
        echo "<li>Entry ID {$entry->id}: {$entry->title} - User: {$entry->user_id} - Contest: {$entry->contest_id} - Likes: {$entry->likes_count}</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Test 5: Controlla voto dell'utente nel contest
    echo "<div class='success'>";
    echo "<h3>🗳️ Test 5: Stato voto User 2 nel Contest 2</h3>";
    try {
        $voteStatus = $voteService->getUserVoteInContest(2, 2);
        echo "<pre>" . json_encode($voteStatus, JSON_PRETTY_PRINT) . "</pre>";
    } catch (Exception $e) {
        echo "<p>❌ ERRORE: " . $e->getMessage() . "</p>";
    }
    echo "</div>";

    echo "<div class='info'>";
    echo "<h3>🎯 Conclusioni</h3>";
    echo "<p>Se tutti i test sopra sono passati correttamente, il sistema di voto semplificato funziona!</p>";
    echo "<p><a href='/test-simple-voting.html' target='_blank'>🔗 Apri l'interfaccia di test</a></p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Errore Generale</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
