<?php

use Illuminate\Support\Facades\Artisan;

// Crea dati di test per il sistema foto
echo "🛠️  Creazione dati di test per Goolliver Photo System\n";
echo "================================================\n\n";

// Crea un utente di test
echo "👤 Creando utente di test...\n";
$user = \App\Models\User::firstOrCreate(
    ['email' => 'test@goolliver.com'],
    [
        'name' => 'Test User',
        'username' => 'testuser',
        'password' => bcrypt('password'),
        'email_verified_at' => now()
    ]
);
echo "   ✅ Utente: {$user->name} (ID: {$user->id})\n\n";

// Crea un contest di test
echo "🏆 Creando contest di test...\n";
$contest = \App\Models\Contest::firstOrCreate(
    ['title' => 'Test Photo Contest'],
    [
        'description' => 'Contest di test per verificare il sistema di upload foto',
        'start_date' => now(),
        'end_date' => now()->addDays(30),
        'max_entries_per_user' => 5,
        'is_public' => true,
        'status' => 'active'
    ]
);
echo "   ✅ Contest: {$contest->title} (ID: {$contest->id})\n";
echo "   📅 Data fine: {$contest->end_date}\n\n";

// Crea un altro utente se necessario
$user2 = \App\Models\User::firstOrCreate(
    ['email' => 'photographer@goolliver.com'],
    [
        'name' => 'Mario Photographer',
        'username' => 'mario_photo',
        'password' => bcrypt('password'),
        'email_verified_at' => now()
    ]
);
echo "👤 Secondo utente: {$user2->name} (ID: {$user2->id})\n\n";

// Verifica storage
echo "📁 Verificando directory storage...\n";
$directories = [
    'photos' => storage_path('app/public/photos'),
    'thumbnails' => storage_path('app/public/photos/thumbnails'),
    'temp' => storage_path('app/temp')
];

foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "   ✅ Creata directory: $name\n";
    } else {
        echo "   ✅ Directory esistente: $name\n";
    }
}

echo "\n🎯 DATI DI TEST PRONTI!\n";
echo "====================\n\n";

echo "📋 Riepilogo:\n";
echo "   - Utenti: " . \App\Models\User::count() . "\n";
echo "   - Contest: " . \App\Models\Contest::count() . "\n";
echo "   - Contest attivi: " . \App\Models\Contest::where('end_date', '>', now())->count() . "\n";
echo "   - Entries: " . \App\Models\Entry::count() . "\n\n";

echo "🌐 URL di test:\n";
echo "   - Pagina Upload: http://127.0.0.1:8000/api/test/photos/page\n";
echo "   - System Status: http://127.0.0.1:8000/api/test/system-status\n";
echo "   - Storage Info: http://127.0.0.1:8000/api/test/photos/storage-info\n\n";

echo "✨ Pronto per testare l'upload foto!\n";
