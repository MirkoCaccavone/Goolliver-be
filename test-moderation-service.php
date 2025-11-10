<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Avvia l'applicazione Laravel
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ModerationService;
use Illuminate\Http\UploadedFile;

try {
    echo "=== TEST MODERATION SERVICE ===\n\n";

    // Test 1: Istanziazione servizio
    echo "1. Testing ModerationService instantiation...\n";
    $moderationService = new ModerationService();
    echo "   ✅ ModerationService created successfully\n\n";

    // Test 2: Configurazione
    echo "2. Testing configuration...\n";
    $config = $moderationService->getConfig();
    echo "   Enabled: " . ($config['enabled'] ? 'YES' : 'NO') . "\n";
    echo "   Default Provider: " . $config['default_provider'] . "\n";
    echo "   Auto Approve Threshold: " . $config['auto_approve_threshold'] . "\n";
    echo "   Auto Reject Threshold: " . $config['auto_reject_threshold'] . "\n\n";

    // Test 3: Test con file mock (senza file reale)
    echo "3. Testing moderation without real file...\n";

    // Crea un file temporaneo per test
    $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
    file_put_contents($tempFile, 'fake image content');

    $uploadedFile = new UploadedFile(
        $tempFile,
        'test-image.jpg',
        'image/jpeg',
        null,
        true
    );

    echo "   Created test file: " . basename($tempFile) . "\n";

    // Test moderazione
    $result = $moderationService->moderatePhoto($uploadedFile);

    echo "   ✅ Moderation completed successfully\n";
    echo "   Status: " . $result['status'] . "\n";
    echo "   Score: " . $result['overall_score'] . "\n";
    echo "   Provider: " . $result['provider'] . "\n";
    echo "   Processing Time: " . $result['processing_time_ms'] . "ms\n";
    echo "   Requires Review: " . ($result['requires_review'] ? 'YES' : 'NO') . "\n";

    if (!empty($result['flagged_reasons'])) {
        echo "   Flagged Reasons: " . count($result['flagged_reasons']) . "\n";
    }

    // Cleanup
    unlink($tempFile);

    echo "\n=== TEST COMPLETATO CON SUCCESSO ===\n";
} catch (\Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
