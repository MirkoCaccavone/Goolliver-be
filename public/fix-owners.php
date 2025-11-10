<?php
// Fix proprietari foto per test
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🔧 Fix Proprietari Foto</h2>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

// Gestisci azione prima di mostrare i dati
if (isset($_POST['action']) && $_POST['action'] === 'fix_owners') {
    echo "<div class='success'>";
    echo "<h3>🛠️ Correzione Proprietari</h3>";

    try {
        // Entry 20 rimane a User #1
        // Entry 21 diventa di User #2
        $entry21 = \App\Models\Entry::find(21);
        if ($entry21) {
            $entry21->user_id = 2;
            $entry21->save();
            echo "<p>✅ Entry #21 ora appartiene a User #2</p>";
        }

        // Se esiste Entry 22, assegnala a User #3
        $entry22 = \App\Models\Entry::find(22);
        if ($entry22) {
            $entry22->user_id = 3;
            $entry22->save();
            echo "<p>✅ Entry #22 ora appartiene a User #3</p>";
        }

        echo "<p><strong>🎉 Correzione completata!</strong></p>";
    } catch (Exception $e) {
        echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
    }

    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>📊 Stato Attuale Entries</h3>";

$entries = \App\Models\Entry::select('id', 'title', 'user_id', 'contest_id', 'likes_count')->orderBy('id')->get();

echo "<table>";
echo "<tr><th>Entry ID</th><th>Titolo</th><th>User ID (Proprietario)</th><th>Contest ID</th><th>Likes</th></tr>";

foreach ($entries as $entry) {
    $bgColor = '';
    if ($entry->id == 20 && $entry->user_id == 1) $bgColor = 'background: #fff3cd;';
    if ($entry->id == 21 && $entry->user_id == 2) $bgColor = 'background: #d4edda;';

    echo "<tr style='{$bgColor}'>";
    echo "<td><strong>{$entry->id}</strong></td>";
    echo "<td>{$entry->title}</td>";
    echo "<td><strong>User #{$entry->user_id}</strong></td>";
    echo "<td>Contest #{$entry->contest_id}</td>";
    echo "<td>{$entry->likes_count}</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>🎯 Test Scenarios Possibili</h3>";

$entry20 = \App\Models\Entry::find(20);
$entry21 = \App\Models\Entry::find(21);

echo "<table>";
echo "<tr><th>Utente</th><th>Può votare Entry #20</th><th>Può votare Entry #21</th><th>Motivo</th></tr>";

$users = [1, 2, 3];
foreach ($users as $userId) {
    echo "<tr>";
    echo "<td><strong>User #{$userId}</strong></td>";

    // Può votare Entry 20?
    if ($entry20) {
        if ($entry20->user_id == $userId) {
            echo "<td>❌ NO</td>";
        } else {
            echo "<td>✅ SÌ</td>";
        }
    } else {
        echo "<td>? Non trovata</td>";
    }

    // Può votare Entry 21?
    if ($entry21) {
        if ($entry21->user_id == $userId) {
            echo "<td>❌ NO</td>";
        } else {
            echo "<td>✅ SÌ</td>";
        }
    } else {
        echo "<td>? Non trovata</td>";
    }

    // Motivo
    if ($entry20 && $entry21) {
        if ($entry20->user_id == $userId || $entry21->user_id == $userId) {
            echo "<td>Ha almeno una foto nel contest</td>";
        } else {
            echo "<td>Non ha foto nel contest</td>";
        }
    } else {
        echo "<td>Entry mancanti</td>";
    }

    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Mostra il pulsante solo se serve correzione
$needsFix = false;
if ($entry20 && $entry21) {
    if ($entry20->user_id == $entry21->user_id) {
        $needsFix = true;
    }
}

if ($needsFix) {
    echo "<div class='info'>";
    echo "<h3>⚡ Correzione Automatica</h3>";
    echo "<p>Problema rilevato: entrambe le foto appartengono allo stesso utente.</p>";
    echo "<form method='post' action=''>";
    echo "<button type='submit' name='action' value='fix_owners' style='background: #007bff; color: white; padding: 15px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🔧 Correggi Proprietari</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<h3>✅ Test System Ready!</h3>";
    echo "<p>I proprietari delle foto sono configurati correttamente per il test.</p>";
    echo "<p><a href='/test-simple-voting.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Vai al Test Interface</a></p>";
    echo "</div>";
}
