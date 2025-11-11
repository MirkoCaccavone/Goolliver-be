<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use App\Models\User;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;

// Test del sistema di crediti
echo "=== TEST SISTEMA CREDITI ===\n\n";

// Trova un utente di test
$user = User::first();
if (!$user) {
    echo "❌ Nessun utente trovato\n";
    exit;
}

echo "👤 Utente di test: {$user->name} (ID: {$user->id})\n";
echo "💰 Crediti attuali: {$user->photo_credits}\n\n";

// Trova una entry di test
$entry = Entry::where('user_id', $user->id)->first();
if (!$entry) {
    echo "❌ Nessuna entry trovata per questo utente\n";
    exit;
}

echo "📸 Entry di test: #{$entry->id}\n";
echo "📊 Status attuale: {$entry->moderation_status}\n";
echo "🏷️ Credit già dato: " . ($entry->credit_given ? 'SÌ' : 'NO') . "\n\n";

echo "=== RISULTATI LOGICA ===\n";

// Simula le diverse azioni
$actions = ['reject', 'approve', 'pending'];

foreach ($actions as $action) {
    echo "\n🎯 Se facciamo '$action':\n";

    // Simula la logica
    $shouldGiveCredit = false;
    $shouldRemoveCredit = false;

    if ($action === 'reject' && !$entry->credit_given) {
        $shouldGiveCredit = true;
        echo "  ✅ Daremmo credito (entry non ha mai dato credito)\n";
    } elseif ($action === 'approve' && $entry->credit_given) {
        $shouldRemoveCredit = true;
        echo "  ❌ Rimuoveremmo credito (entry ha già dato credito)\n";
    } else {
        echo "  ⚪ Nessun movimento crediti\n";
        if ($action === 'reject' && $entry->credit_given) {
            echo "     Motivo: Entry ha già dato credito\n";
        } elseif ($action === 'approve' && !$entry->credit_given) {
            echo "     Motivo: Entry non ha mai dato credito\n";
        }
    }
}

echo "\n\n=== CREDITI DISPONIBILI ===\n";
echo "Crediti totali utente: {$user->photo_credits}\n";
if ($user->credit_notes) {
    echo "Note crediti:\n" . $user->credit_notes . "\n";
} else {
    echo "Nessuna nota sui crediti\n";
}

echo "\n✅ Test completato!\n";
