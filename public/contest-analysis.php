<?php
// Analisi completa contest e correzione per test
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🔍 Analisi Contest e Correzione Test</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background-color: #f2f2f2; }
button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
.success-btn { background: #28a745; }
</style>";

// Gestisci azioni
if (isset($_POST['action'])) {
    echo "<div class='success'>";
    echo "<h3>🛠️ Azione Eseguita</h3>";

    try {
        if ($_POST['action'] === 'move_to_contest1') {
            // Sposta tutte le entries al Contest #1
            $entries = \App\Models\Entry::all();
            foreach ($entries as $entry) {
                $entry->contest_id = 1;
                $entry->save();
                echo "<p>✅ Entry #{$entry->id} spostata al Contest #1</p>";
            }
        }

        if ($_POST['action'] === 'create_test_entries') {
            // Crea entries di test nel Contest #1 con utenti diversi
            $contest1 = \App\Models\Contest::find(1);
            if (!$contest1) {
                // Crea Contest #1 se non esiste
                $contest1 = \App\Models\Contest::create([
                    'title' => 'Test Contest #1',
                    'description' => 'Contest per test del sistema di voto',
                    'status' => 'voting',
                    'start_date' => now(),
                    'end_date' => now()->addDays(30),
                ]);
                echo "<p>✅ Contest #1 creato</p>";
            }

            // Crea entries per diversi utenti
            $testEntries = [
                ['user_id' => 1, 'title' => 'Foto User #1', 'filename' => 'test1.jpg'],
                ['user_id' => 2, 'title' => 'Foto User #2', 'filename' => 'test2.jpg'],
                ['user_id' => 3, 'title' => 'Foto User #3', 'filename' => 'test3.jpg'],
                ['user_id' => 4, 'title' => 'Foto User #4', 'filename' => 'test4.jpg'],
            ];

            // Cancella entries esistenti nel contest #1
            \App\Models\Entry::where('contest_id', 1)->delete();

            foreach ($testEntries as $data) {
                \App\Models\Entry::create([
                    'user_id' => $data['user_id'],
                    'contest_id' => 1,
                    'title' => $data['title'],
                    'filename' => $data['filename'],
                    'image_path' => '/storage/photos/' . $data['filename'],
                    'moderation_status' => 'approved',
                    'processing_status' => 'completed',
                    'likes_count' => 0,
                    'vote_score' => 0,
                ]);
                echo "<p>✅ Entry '{$data['title']}' creata per User #{$data['user_id']}</p>";
            }
        }

        echo "<p><strong>🎉 Operazione completata!</strong></p>";
    } catch (Exception $e) {
        echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
    }

    echo "</div>";
}

// Mostra stato attuale
echo "<div class='info'>";
echo "<h3>📊 Stato Attuale Contest</h3>";

$contests = \App\Models\Contest::with(['entries'])->get();

foreach ($contests as $contest) {
    echo "<h4>Contest #{$contest->id}: {$contest->title}</h4>";
    echo "<table>";
    echo "<tr><th>Entry ID</th><th>Titolo</th><th>User ID</th><th>Likes</th><th>Status</th></tr>";

    if ($contest->entries->count() > 0) {
        foreach ($contest->entries as $entry) {
            echo "<tr>";
            echo "<td><strong>#{$entry->id}</strong></td>";
            echo "<td>{$entry->title}</td>";
            echo "<td><strong>User #{$entry->user_id}</strong></td>";
            echo "<td>{$entry->likes_count}</td>";
            echo "<td>{$entry->status}</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5' style='text-align: center; color: #666;'>Nessuna entry in questo contest</td></tr>";
    }

    echo "</table>";

    // Analisi partecipanti
    $participants = $contest->entries->pluck('user_id')->unique();
    echo "<p><strong>Partecipanti:</strong> " . $participants->count() . " utenti (" . $participants->implode(', ') . ")</p>";
}

echo "</div>";

// Analisi problemi
echo "<div class='warning'>";
echo "<h3>⚠️ Analisi Problemi</h3>";

$currentTestContest = \App\Models\Contest::find(2);
if ($currentTestContest) {
    $entries = $currentTestContest->entries;
    $participants = $entries->pluck('user_id')->unique();

    echo "<p><strong>Problema rilevato:</strong> Il test interface usa Contest #{$currentTestContest->id} che ha solo {$participants->count()} partecipanti.</p>";
    echo "<p>Con solo {$participants->count()} partecipanti, gli altri utenti (User #3, #4, #5...) non possono votare perché non hanno partecipato al contest.</p>";

    echo "<h4>Scenario di voto attuale:</h4>";
    echo "<table>";
    echo "<tr><th>Utente</th><th>Può Votare?</th><th>Foto Votabili</th><th>Motivo</th></tr>";

    for ($userId = 1; $userId <= 5; $userId++) {
        $hasEntry = $entries->where('user_id', $userId)->count() > 0;
        $votableEntries = $entries->where('user_id', '!=', $userId);

        echo "<tr>";
        echo "<td><strong>User #{$userId}</strong></td>";

        if ($hasEntry) {
            echo "<td>✅ SÌ</td>";
            echo "<td>{$votableEntries->count()} foto</td>";
            echo "<td>Ha partecipato al contest</td>";
        } else {
            echo "<td>❌ NO</td>";
            echo "<td>0 foto</td>";
            echo "<td>Non ha partecipato al contest</td>";
        }

        echo "</tr>";
    }

    echo "</table>";
}

echo "</div>";

// Soluzioni
echo "<div class='info'>";
echo "<h3>💡 Soluzioni Disponibili</h3>";

echo "<p>Per testare correttamente il sistema di voto, abbiamo 2 opzioni:</p>";

echo "<form method='post' style='display: inline-block; margin: 10px;'>";
echo "<button type='submit' name='action' value='move_to_contest1' class='success-btn'>📋 Opzione 1: Sposta tutto al Contest #1</button>";
echo "<p style='margin: 5px 0; font-size: 14px; color: #666;'>Sposta tutte le entries esistenti al Contest #1</p>";
echo "</form>";

echo "<form method='post' style='display: inline-block; margin: 10px;'>";
echo "<button type='submit' name='action' value='create_test_entries' class='success-btn'>🎯 Opzione 2: Crea Contest #1 con 4 utenti</button>";
echo "<p style='margin: 5px 0; font-size: 14px; color: #666;'>Crea un nuovo Contest #1 con foto di User #1, #2, #3, #4</p>";
echo "</form>";

echo "</div>";

// Link per test
$contest1 = \App\Models\Contest::find(1);
if ($contest1 && $contest1->entries->count() >= 3) {
    echo "<div class='success'>";
    echo "<h3>✅ Sistema Pronto per Test!</h3>";
    echo "<p>Contest #1 configurato con {$contest1->entries->count()} partecipanti.</p>";
    echo "<p><a href='/test-simple-voting.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Vai al Test Interface</a></p>";
    echo "<p><em>Nota: Modifica il test interface per usare Contest #1 invece di Contest #2</em></p>";
    echo "</div>";
}
