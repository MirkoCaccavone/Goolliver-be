<?php

// Quick test script for photo upload system
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->boot();

echo "🔍 GOOLLIVER PHOTO SYSTEM TEST\n";
echo "================================\n\n";

// Test 1: Classes
echo "📋 1. CLASSES CHECK:\n";
echo "   PhotoService: " . (class_exists('App\Services\PhotoService') ? '✅ OK' : '❌ MISSING') . "\n";
echo "   PhotoController: " . (class_exists('App\Http\Controllers\Api\PhotoController') ? '✅ OK' : '❌ MISSING') . "\n";
echo "   Entry Model: " . (class_exists('App\Models\Entry') ? '✅ OK' : '❌ MISSING') . "\n";
echo "   PhotoUploadException: " . (class_exists('App\Exceptions\PhotoUploadException') ? '✅ OK' : '❌ MISSING') . "\n\n";

// Test 2: Database
echo "🗄️  2. DATABASE CHECK:\n";
echo "   entries table: " . (\Illuminate\Support\Facades\Schema::hasTable('entries') ? '✅ EXISTS' : '❌ MISSING') . "\n";
echo "   moderation_status column: " . (\Illuminate\Support\Facades\Schema::hasColumn('entries', 'moderation_status') ? '✅ EXISTS' : '❌ MISSING') . "\n";
echo "   processing_status column: " . (\Illuminate\Support\Facades\Schema::hasColumn('entries', 'processing_status') ? '✅ EXISTS' : '❌ MISSING') . "\n";
echo "   file_size column: " . (\Illuminate\Support\Facades\Schema::hasColumn('entries', 'file_size') ? '✅ EXISTS' : '❌ MISSING') . "\n\n";

// Test 3: Storage
echo "📁 3. STORAGE CHECK:\n";
$photosPath = storage_path('app/public/photos');
$thumbsPath = storage_path('app/public/photos/thumbnails');
$tempPath = storage_path('app/temp');

echo "   photos dir: " . (is_dir($photosPath) ? '✅ EXISTS' : '❌ MISSING') . " ($photosPath)\n";
echo "   thumbnails dir: " . (is_dir($thumbsPath) ? '✅ EXISTS' : '❌ MISSING') . " ($thumbsPath)\n";
echo "   temp dir: " . (is_dir($tempPath) ? '✅ EXISTS' : '❌ MISSING') . " ($tempPath)\n";

echo "   photos writable: " . (is_writable($photosPath) ? '✅ YES' : '❌ NO') . "\n";
echo "   thumbnails writable: " . (is_writable($thumbsPath) ? '✅ YES' : '❌ NO') . "\n";
echo "   temp writable: " . (is_writable($tempPath) ? '✅ YES' : '❌ NO') . "\n\n";

// Test 4: Sample Data
echo "📊 4. SAMPLE DATA:\n";
$contestsCount = \App\Models\Contest::count();
$usersCount = \App\Models\User::count();
$entriesCount = \App\Models\Entry::count();

echo "   Contests: $contestsCount\n";
echo "   Users: $usersCount\n";
echo "   Entries: $entriesCount\n";

if ($contestsCount > 0) {
    $contest = \App\Models\Contest::first();
    echo "   Sample Contest: '{$contest->title}' (ID: {$contest->id})\n";
}

if ($usersCount > 0) {
    $user = \App\Models\User::first();
    echo "   Sample User: '{$user->name}' (ID: {$user->id})\n";
}

echo "\n";

// Test 5: Configuration
echo "⚙️  5. CONFIGURATION:\n";
echo "   Max upload size: " . ini_get('upload_max_filesize') . "\n";
echo "   Post max size: " . ini_get('post_max_size') . "\n";
echo "   File uploads: " . (ini_get('file_uploads') ? '✅ ENABLED' : '❌ DISABLED') . "\n";
echo "   PHP version: " . PHP_VERSION . "\n";
echo "   Laravel version: " . app()->version() . "\n\n";

echo "🚀 SYSTEM STATUS: ";

// Overall check
$allGood = class_exists('App\Services\PhotoService') &&
    \Illuminate\Support\Facades\Schema::hasTable('entries') &&
    \Illuminate\Support\Facades\Schema::hasColumn('entries', 'moderation_status') &&
    is_dir($photosPath) &&
    is_writable($photosPath) &&
    $contestsCount > 0 &&
    $usersCount > 0;

if ($allGood) {
    echo "✅ READY FOR PHOTO UPLOADS!\n";
    echo "\n📸 Test URLs:\n";
    echo "   - Test Page: http://127.0.0.1:8000/api/test/photos/page\n";
    echo "   - System Status: http://127.0.0.1:8000/api/test/system-status\n";
    echo "   - Storage Info: http://127.0.0.1:8000/api/test/photos/storage-info\n";
} else {
    echo "⚠️  SOME ISSUES FOUND - CHECK ABOVE\n";
}

echo "\n================================\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
