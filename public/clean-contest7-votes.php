<?php
// Reset voti Contest #7
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🧹 Reset Voti Contest #7</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background-color: #f2f2f2; }
button { background: #dc3545; color: white; padding: 15px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px; }
.test-btn { background: #28a745; }
</style>";

// Gestisci azione
if (isset($_POST['action']) && $_POST['action'] === 'reset_votes') {
    echo "<div class='success'>";
    echo "<h3>🧹 Reset Voti in Corso...</h3>";

    try {
        // 1. Cancella tutti i voti per le entries del contest #7
        $contest7Entries = \App\Models\Entry::where('contest_id', 7)->pluck('id');

        $deletedVotes = \App\Models\Vote::whereIn('entry_id', $contest7Entries)->delete();
        echo "<p>✅ Cancellati {$deletedVotes} voti dalle entries del Contest #7</p>";

        // 2. Reset contatori likes_count e vote_score delle entries
        \App\Models\Entry::where('contest_id', 7)->update([
            'likes_count' => 0,
            'vote_score' => 0
        ]);
        echo "<p>✅ Reset contatori likes_count e vote_score per tutte le entries del Contest #7</p>";

        echo "<p><strong>🎉 Reset completato con successo!</strong></p>";
    } catch (Exception $e) {
        echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
    }

    echo "</div>";
}

// Mostra stato attuale
echo "<div class='info'>";
echo "<h3>📊 Stato Attuale Contest #7</h3>";

try {
    // Informazioni contest
    $contest = \App\Models\Contest::find(7);
    if ($contest) {
        echo "<p><strong>Contest:</strong> #{$contest->id} - {$contest->title}</p>";
        echo "<p><strong>Status:</strong> {$contest->status}</p>";
    }

    // Entries nel contest
    $entries = \App\Models\Entry::where('contest_id', 7)
        ->select('id', 'title', 'user_id', 'likes_count', 'vote_score')
        ->orderBy('id')
        ->get();

    echo "<h4>📸 Entries nel Contest #7</h4>";
    echo "<table>";
    echo "<tr><th>Entry ID</th><th>Titolo</th><th>User ID</th><th>Likes Count</th><th>Vote Score</th></tr>";

    foreach ($entries as $entry) {
        $bgColor = $entry->likes_count > 0 ? 'background: #fff3cd;' : '';
        echo "<tr style='{$bgColor}'>";
        echo "<td><strong>#{$entry->id}</strong></td>";
        echo "<td>{$entry->title}</td>";
        echo "<td><strong>User #{$entry->user_id}</strong></td>";
        echo "<td>{$entry->likes_count}</td>";
        echo "<td>{$entry->vote_score}</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Voti attualmente nel sistema per questo contest
    $entryIds = $entries->pluck('id');
    $votes = \App\Models\Vote::whereIn('entry_id', $entryIds)
        ->select('id', 'user_id', 'entry_id')
        ->get();

    echo "<h4>🗳️ Voti Attuali</h4>";
    if ($votes->count() > 0) {
        echo "<table>";
        echo "<tr><th>Vote ID</th><th>User ID</th><th>Entry ID</th></tr>";

        foreach ($votes as $vote) {
            echo "<tr>";
            echo "<td>#{$vote->id}</td>";
            echo "<td>User #{$vote->user_id}</td>";
            echo "<td>Entry #{$vote->entry_id}</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "<p><strong>Totale voti:</strong> {$votes->count()}</p>";
    } else {
        echo "<p style='color: #28a745;'><strong>✅ Nessun voto presente - Contest pulito!</strong></p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Errore nel recuperare i dati: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Analisi per utente
echo "<div class='warning'>";
echo "<h3>👥 Analisi per Utente</h3>";

$participants = [25, 26, 27, 28, 29];
echo "<table>";
echo "<tr><th>Utente</th><th>Ha Entry?</th><th>Entry ID</th><th>Ha già votato?</th><th>Voto su Entry</th></tr>";

foreach ($participants as $userId) {
    $userEntry = $entries->where('user_id', $userId)->first();
    $userVote = $votes->where('user_id', $userId)->first();

    echo "<tr>";
    echo "<td><strong>User #{$userId}</strong></td>";
    echo "<td>" . ($userEntry ? "✅ SÌ" : "❌ NO") . "</td>";
    echo "<td>" . ($userEntry ? "#{$userEntry->id}" : "-") . "</td>";
    echo "<td>" . ($userVote ? "❌ SÌ" : "✅ NO") . "</td>";
    echo "<td>" . ($userVote ? "Entry #{$userVote->entry_id}" : "-") . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Pulsanti azione
if ($votes->count() > 0) {
    echo "<div class='warning'>";
    echo "<h3>⚠️ Azione Richiesta</h3>";
    echo "<p>Sono presenti {$votes->count()} voti nel Contest #7. Per testare correttamente il sistema, è necessario resettare tutti i voti.</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='action' value='reset_votes'>🧹 Reset Tutti i Voti del Contest #7</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>✅ Pronto per Test!</h3>";
    echo "<p>Il Contest #7 è pulito e pronto per i test.</p>";
    echo "<button class='test-btn' onclick=\"window.open('/test-simple-voting.html', '_blank')\">🔗 Apri Test Interface</button>";
    echo "</div>";
}
