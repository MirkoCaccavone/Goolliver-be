<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SystemTestController extends Controller
{
    /**
     * Test API status and configuration
     */
    public function systemStatus(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'OK',
                'timestamp' => now()->toISOString(),
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'environment' => app()->environment(),
                    'timezone' => config('app.timezone'),
                    'locale' => app()->getLocale()
                ],
                'database' => [
                    'connection' => 'OK',
                    'users_count' => User::count(),
                    'contests_count' => Contest::count()
                ],
                'storage' => [
                    'photos_disk' => config('filesystems.disks.photos.driver'),
                    'photos_path' => storage_path('app/public/photos'),
                    'photos_exists' => is_dir(storage_path('app/public/photos')),
                    'photos_writable' => is_writable(storage_path('app/public/photos'))
                ],
                'features' => [
                    'notifications' => class_exists('\App\Models\Notification'),
                    'photo_service' => class_exists('\App\Services\PhotoService'),
                    'photo_controller' => class_exists('\App\Http\Controllers\Api\PhotoController')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Test photo upload workflow
     */
    public function photoSystemTest(): JsonResponse
    {
        try {
            $tests = [];

            // Test 1: Storage directories
            $tests['storage_directories'] = [
                'photos' => is_dir(storage_path('app/public/photos')),
                'thumbnails' => is_dir(storage_path('app/public/photos/thumbnails')),
                'temp' => is_dir(storage_path('app/temp'))
            ];

            // Test 2: Write permissions
            $tests['write_permissions'] = [
                'photos' => is_writable(storage_path('app/public/photos')),
                'thumbnails' => is_writable(storage_path('app/public/photos/thumbnails')),
                'temp' => is_writable(storage_path('app/temp'))
            ];

            // Test 3: Required classes
            $tests['required_classes'] = [
                'PhotoService' => class_exists('\App\Services\PhotoService'),
                'PhotoController' => class_exists('\App\Http\Controllers\Api\PhotoController'),
                'PhotoUploadRequest' => class_exists('\App\Http\Requests\PhotoUploadRequest'),
                'PhotoUploadException' => class_exists('\App\Exceptions\PhotoUploadException'),
                'EntryPolicy' => class_exists('\App\Policies\EntryPolicy')
            ];

            // Test 4: Database structure
            try {
                $tests['database_structure'] = [
                    'entries_table' => Schema::hasTable('entries'),
                    'moderation_score_column' => Schema::hasColumn('entries', 'moderation_score'),
                    'processing_status_column' => Schema::hasColumn('entries', 'processing_status'),
                    'file_size_column' => Schema::hasColumn('entries', 'file_size')
                ];
            } catch (\Exception $e) {
                $tests['database_structure'] = ['error' => $e->getMessage()];
            }

            // Test 5: Available contests and users
            try {
                $tests['test_data'] = [
                    'active_contests' => Contest::where('end_date', '>', now())->count(),
                    'total_users' => User::count(),
                    'sample_contest' => Contest::first()?->only(['id', 'title', 'end_date']),
                    'sample_user' => User::first()?->only(['id', 'name', 'email'])
                ];
            } catch (\Exception $e) {
                $tests['test_data'] = ['error' => $e->getMessage()];
            }

            // Overall status
            $allPassed = true;
            foreach ($tests as $category => $results) {
                if (is_array($results)) {
                    foreach ($results as $key => $result) {
                        if (is_bool($result) && !$result) {
                            $allPassed = false;
                            break 2;
                        }
                    }
                }
            }

            return response()->json([
                'status' => $allPassed ? 'READY' : 'ISSUES_FOUND',
                'overall_ready' => $allPassed,
                'timestamp' => now()->toISOString(),
                'tests' => $tests,
                'recommendations' => $allPassed ?
                    ['✅ Sistema pronto per test upload foto'] :
                    ['⚠️ Controllare i test falliti prima di procedere']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'ERROR',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Security check for photo system
     */
    public function securityCheck(): JsonResponse
    {
        try {
            $checks = [];

            // File upload security
            $checks['file_security'] = [
                'max_upload_size' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled',
                'temp_dir' => sys_get_temp_dir(),
                'temp_dir_writable' => is_writable(sys_get_temp_dir())
            ];

            // Storage security
            $checks['storage_security'] = [
                'photos_outside_public' => !str_contains(storage_path('app/public/photos'), public_path()),
                'htaccess_protection' => file_exists(storage_path('app/public/.htaccess')),
                'directory_listing_blocked' => !file_exists(storage_path('app/public/photos/index.php'))
            ];

            // Application security
            $checks['app_security'] = [
                'debug_mode' => config('app.debug') ? 'ON (⚠️ Disabilitare in produzione)' : 'OFF',
                'app_key_set' => !empty(config('app.key')),
                'sanctum_configured' => config('sanctum.stateful') !== null,
                'cors_configured' => config('cors.paths') !== null
            ];

            // Validation checks
            $checks['validation'] = [
                'photo_upload_request_exists' => class_exists('\App\Http\Requests\PhotoUploadRequest'),
                'photo_exception_exists' => class_exists('\App\Exceptions\PhotoUploadException'),
                'entry_policy_exists' => class_exists('\App\Policies\EntryPolicy')
            ];

            return response()->json([
                'security_status' => 'ANALYZED',
                'timestamp' => now()->toISOString(),
                'checks' => $checks,
                'recommendations' => [
                    '🔒 Verificare che file_uploads sia abilitato',
                    '🛡️ Configurare .htaccess per bloccare accesso diretto',
                    '⚠️ Disabilitare debug in produzione',
                    '🔐 Verificare configurazione CORS per upload',
                    '📁 Monitoring spazio disco per upload'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'security_status' => 'ERROR',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
}
