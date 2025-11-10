<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Entry;
use App\Models\User;
use App\Services\PhotoService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PhotoTestController extends Controller
{
    public function __construct(
        private PhotoService $photoService
    ) {}

    /**
     * Test page for photo upload system
     */
    public function testPage()
    {
        $contests = Contest::where('end_date', '>', now())->get();
        $users = User::take(5)->get();

        return view('photo-test', compact('contests', 'users'));
    }

    /**
     * Simple test upload endpoint
     */
    public function testUpload(Request $request)
    {
        try {
            // Validazione base
            $request->validate([
                'contest_id' => 'required|exists:contests,id',
                'user_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'photo' => 'required|image|mimes:jpg,jpeg,png|max:10240'
            ]);

            // Ottieni contest e user
            $contest = Contest::findOrFail($request->contest_id);
            $user = User::findOrFail($request->user_id);

            // Check existing entry
            $existingEntry = Entry::where('user_id', $user->id)
                ->where('contest_id', $contest->id)
                ->first();

            if ($existingEntry) {
                return response()->json([
                    'error' => 'User already has an entry for this contest'
                ], 409);
            }

            // Upload photo using PhotoService - it creates and returns the Entry
            $entry = $this->photoService->uploadPhoto(
                $request->file('photo'),
                $user->id,
                $contest->id,
                $request->only(['title', 'description', 'location', 'camera_model', 'settings'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully!',
                'entry' => [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'photo_url' => $entry->photo_url,
                    'thumbnail_url' => $entry->thumbnail_url,
                    'processing_status' => $entry->processing_status,
                    'moderation_status' => $entry->moderation_status,
                    'dimensions' => $entry->dimensions,
                    'file_size' => number_format($entry->file_size / 1024, 1) . ' KB'
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Photo test upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get photo storage info
     */
    public function storageInfo()
    {
        try {
            $photosPath = storage_path('app/public/photos');
            $thumbsPath = storage_path('app/public/photos/thumbnails');
            $tempPath = storage_path('app/temp');

            $info = [
                'storage_paths' => [
                    'photos' => $photosPath,
                    'thumbnails' => $thumbsPath,
                    'temp' => $tempPath
                ],
                'directories_exist' => [
                    'photos' => is_dir($photosPath),
                    'thumbnails' => is_dir($thumbsPath),
                    'temp' => is_dir($tempPath)
                ],
                'permissions' => [
                    'photos' => is_writable($photosPath),
                    'thumbnails' => is_writable($thumbsPath),
                    'temp' => is_writable($tempPath)
                ],
                'photo_count' => count(Storage::disk('photos')->files()),
                'total_size' => $this->getDirectorySize($photosPath)
            ];

            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get storage info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List uploaded photos
     */
    public function listPhotos()
    {
        $entries = Entry::with(['user:id,name', 'contest:id,title'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'photos' => $entries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'photo_url' => $entry->photo_url,
                    'thumbnail_url' => $entry->thumbnail_url,
                    'user' => $entry->user->name,
                    'contest' => $entry->contest->title,
                    'file_size' => number_format($entry->file_size / 1024, 1) . ' KB',
                    'moderation_status' => $entry->moderation_status,
                    'processing_status' => $entry->processing_status,
                    'created_at' => $entry->created_at->format('d/m/Y H:i')
                ];
            })
        ]);
    }

    private function getDirectorySize($path)
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return number_format($size / 1024 / 1024, 2) . ' MB';
    }
}
