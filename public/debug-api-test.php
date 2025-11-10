<?php
// Test API diretta per debug classifica
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🔧 Test API Debug - Classifica e Voti</h2>";
echo "<style>
.test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
.success { background: #d4edda; border-color: #c3e6cb; }
.error { background: #f8d7da; border-color: #f5c6cb; }
.info { background: #d1ecf1; border-color: #bee5eb; }
</style>";

// Test 1: Verifica dati entry esistenti
echo "<div class='test-section info'>";
echo "<h3>📊 Test 1: Entry Esistenti</h3>";

$entries = App\Models\Entry::take(3)->get();
echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Title</th><th>Contest</th><th>Likes</th><th>Ratings</th><th>Avg Rating</th><th>Vote Score</th></tr>";

foreach ($entries as $entry) {
    echo "<tr>";
    echo "<td>{$entry->id}</td>";
    echo "<td>{$entry->title}</td>";
    echo "<td>{$entry->contest_id}</td>";
    echo "<td>{$entry->likes_count}</td>";
    echo "<td>{$entry->ratings_count}</td>";
    echo "<td>{$entry->average_rating}</td>";
    echo "<td>{$entry->vote_score}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test 2: Aggiungi un like e verifica aggiornamento
echo "<div class='test-section'>";
echo "<h3>💖 Test 2: Aggiunta Like</h3>";

try {
    $testEntry = $entries->first();
    if ($testEntry) {
        echo "<p><strong>Prima del like:</strong></p>";
        echo "<p>Entry ID {$testEntry->id}: Likes = {$testEntry->likes_count}, Score = {$testEntry->vote_score}</p>";

        // Aggiungi like tramite VoteService
        $voteService = app(App\Services\VoteService::class);
        $result = $voteService->toggleLike($testEntry->id, 1, '127.0.0.1', 'Debug Test');

        echo "<div class='success'>";
        echo "<p><strong>Risultato API:</strong></p>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";

        // Ricarica entry per vedere se è cambiata
        $testEntry->refresh();
        echo "<p><strong>Dopo il like:</strong></p>";
        echo "<p>Entry ID {$testEntry->id}: Likes = {$testEntry->likes_count}, Score = {$testEntry->vote_score}</p>";

        if ($result['action'] === 'liked') {
            echo "<p style='color: green;'>✅ Like aggiunto correttamente</p>";
        } else {
            echo "<p style='color: blue;'>🔄 Like rimosso (era già presente)</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Nessuna entry trovata per il test</p>";
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
    echo "</div>";
}
echo "</div>";

// Test 3: Test classifica dopo il like
echo "<div class='test-section'>";
echo "<h3>🏆 Test 3: Classifica Dopo Modifica</h3>";

try {
    // Prendi il primo contest disponibile
    $contest = App\Models\Contest::first();
    if ($contest) {
        echo "<p>Testing classifica per Contest ID: {$contest->id}</p>";

        $voteService = app(App\Services\VoteService::class);
        $leaderboard = $voteService->getTopEntries($contest->id, 5, 'vote_score');

        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr><th>Pos</th><th>ID</th><th>Title</th><th>Likes</th><th>Ratings</th><th>Avg</th><th>Score</th></tr>";

        foreach ($leaderboard as $index => $entry) {
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>{$entry['id']}</td>";
            echo "<td>" . ($entry['title'] ?? 'N/A') . "</td>";
            echo "<td>{$entry['likes_count']}</td>";
            echo "<td>{$entry['ratings_count']}</td>";
            echo "<td>{$entry['average_rating']}</td>";
            echo "<td><strong>{$entry['vote_score']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Nessun contest trovato</p>";
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ Errore classifica: " . $e->getMessage() . "</p>";
    echo "</div>";
}
echo "</div>";

// Test 4: API HTTP diretta
echo "<div class='test-section'>";
echo "<h3>🌐 Test 4: API HTTP Response</h3>";
echo "<p>Testa manualmente questi endpoint:</p>";
echo "<ul>";
echo "<li><a href='/api/votes/contests/2/leaderboard?limit=5' target='_blank'>GET /api/votes/contests/2/leaderboard?limit=5</a></li>";
echo "<li><a href='/api/votes/contests/3/leaderboard?limit=5' target='_blank'>GET /api/votes/contests/3/leaderboard?limit=5</a></li>";
echo "</ul>";
echo "</div>";

echo "<div class='test-section info'>";
echo "<h3>🔄 Suggerimenti Debug</h3>";
echo "<ol>";
echo "<li>Se le statistiche dell'entry si aggiornano ma la classifica non cambia → problema query/ordinamento</li>";
echo "<li>Se le statistiche non si aggiornano → problema nel VoteService</li>";
echo "<li>Se tutto sembra OK qui ma non nel frontend → problema JavaScript/timing</li>";
echo "</ol>";
echo "</div>";
