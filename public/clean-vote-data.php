<?php
// Script per pulire e sincronizzare i dati di voto
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<h2>🧹 Pulizia e Sincronizzazione Dati di Voto</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

try {
    DB::beginTransaction();

    // Step 1: Mostra stato attuale
    echo "<div class='info'>";
    echo "<h3>📊 Stato Prima della Pulizia</h3>";

    $votes = DB::table('votes')->where('vote_type', 'like')->get();
    echo "<p>Voti like nel database: " . count($votes) . "</p>";

    if (count($votes) > 0) {
        echo "<ul>";
        foreach ($votes as $vote) {
            echo "<li>Vote ID {$vote->id}: User {$vote->user_id} → Entry {$vote->entry_id}</li>";
        }
        echo "</ul>";
    }

    $entries = \App\Models\Entry::select('id', 'title', 'likes_count', 'vote_score', 'user_id', 'contest_id')->get();
    echo "<p>Statistiche entries:</p><ul>";
    foreach ($entries as $entry) {
        echo "<li>Entry {$entry->id} ({$entry->title}): {$entry->likes_count} likes, score: {$entry->vote_score}</li>";
    }
    echo "</ul>";
    echo "</div>";

    // Step 2: Pulisci tutti i voti esistenti
    echo "<div class='warning'>";
    echo "<h3>🗑️ Pulizia Voti Esistenti</h3>";

    $deletedVotes = DB::table('votes')->where('vote_type', 'like')->delete();
    echo "<p>✅ Eliminati {$deletedVotes} voti like</p>";
    echo "</div>";

    // Step 3: Reset contatori nelle entries
    echo "<div class='warning'>";
    echo "<h3>🔄 Reset Contatori Entry</h3>";

    DB::table('entries')->update([
        'likes_count' => 0,
        'vote_score' => 0
    ]);
    echo "<p>✅ Reset contatori likes_count e vote_score per tutte le entries</p>";
    echo "</div>";

    // Step 4: Verifica stato pulito
    echo "<div class='success'>";
    echo "<h3>✨ Stato Dopo Pulizia</h3>";

    $remainingVotes = DB::table('votes')->where('vote_type', 'like')->count();
    echo "<p>Voti like rimanenti: {$remainingVotes}</p>";

    $entries = \App\Models\Entry::select('id', 'title', 'likes_count', 'vote_score')->get();
    echo "<p>Statistiche entries dopo reset:</p><ul>";
    foreach ($entries as $entry) {
        echo "<li>Entry {$entry->id} ({$entry->title}): {$entry->likes_count} likes, score: {$entry->vote_score}</li>";
    }
    echo "</ul>";
    echo "</div>";

    DB::commit();

    echo "<div class='success'>";
    echo "<h3>🎉 Pulizia Completata!</h3>";
    echo "<p>Ora tutti i voti sono stati rimossi e i contatori sono a zero.</p>";
    echo "<p><strong>Prossimi passi:</strong></p>";
    echo "<ol>";
    echo "<li><a href='/test-simple-voting.html' target='_blank'>🔗 Apri l'interfaccia di test</a></li>";
    echo "<li>Seleziona un utente (es. User #2)</li>";
    echo "<li>Prova a votare - dovrebbe funzionare correttamente ora</li>";
    echo "<li>Controlla che i contatori si aggiornino</li>";
    echo "</ol>";
    echo "</div>";
} catch (Exception $e) {
    DB::rollback();
    echo "<div class='error'>";
    echo "<h3>❌ Errore Durante la Pulizia</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
    echo "</div>";
}
