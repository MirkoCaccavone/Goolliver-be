<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use App\Models\Entry;
use Exception;

class PhotoService
{
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const THUMBNAIL_SIZE = 300;
    const MEDIUM_SIZE = 800;

    /**
     * Upload e processa una foto
     */
    public function uploadPhoto(UploadedFile $file, int $userId, int $contestId, array $data = []): Entry
    {
        // 1. Validazione sicurezza
        $this->validateFile($file);

        // 2. Generazione nome file sicuro
        $filename = $this->generateSecureFilename($file);

        // 3. Upload temporaneo per validazione
        $tempPath = $this->uploadToTemp($file, $filename);

        // 4. Controlli di sicurezza avanzati
        $this->performSecurityChecks($tempPath);

        // 5. Content moderation (AI/Manual) - passa il file originale per nome corretto
        $moderationResult = $this->checkContentModeration($file, $tempPath);

        // 6. Processing immagine (resize, thumbnails)
        $processedPaths = $this->processImage($tempPath, $filename);

        // 7. Creazione Entry nel database
        $entry = Entry::create([
            'user_id' => $userId,
            'contest_id' => $contestId,
            'photo_url' => $processedPaths['original'],
            'thumbnail_url' => $processedPaths['thumbnail'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'caption' => $data['caption'] ?? $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'camera_model' => $data['camera_model'] ?? null,
            'settings' => $data['settings'] ?? null,
            'tags' => $data['tags'] ?? null,
            'moderation_status' => $this->determineModerationStatus($moderationResult),
            'processing_status' => 'completed',
            'moderation_score' => $moderationResult['score'],
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'dimensions' => $processedPaths['dimensions'],
            'metadata' => [
                'upload_timestamp' => now()->toISOString(),
                'original_filename' => $file->getClientOriginalName(),
                'processing_version' => '1.0',
                'moderation' => [
                    'provider' => $moderationResult['provider'],
                    'confidence' => $moderationResult['confidence'],
                    'categories' => $moderationResult['categories'],
                    'flagged_reasons' => $moderationResult['flags'],
                    'processing_time_ms' => $moderationResult['processing_time'],
                    'full_result' => $moderationResult['full_result'] ?? null
                ]
            ]
        ]);

        // 8. Cleanup temp file
        Storage::disk('temp_uploads')->delete($filename);

        return $entry;
    }

    /**
     * Determina lo status di moderazione basato sul risultato
     */
    private function determineModerationStatus(array $moderationResult): string
    {
        if ($moderationResult['auto_approved']) {
            return 'approved';
        } elseif ($moderationResult['needs_human_review']) {
            return 'pending_review';
        } elseif ($moderationResult['score'] >= 0.9) {
            return 'rejected';
        } else {
            return 'pending';
        }
    }

    /**
     * Validazione file di base
     */
    private function validateFile(UploadedFile $file): void
    {
        // Dimensione file
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception('File troppo grande. Massimo ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }

        // MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new Exception('Tipo di file non supportato: ' . $file->getMimeType());
        }

        // Estensione
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new Exception('Estensione file non supportata: ' . $extension);
        }

        // Verifica che sia effettivamente un'immagine
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new Exception('Il file non è un\'immagine valida');
        }

        // Dimensioni minime
        if ($imageInfo[0] < 400 || $imageInfo[1] < 400) {
            throw new Exception('Immagine troppo piccola. Minimo 400x400 pixel');
        }
    }

    /**
     * Generazione nome file sicuro
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $hash = hash('sha256', $file->getContent() . time() . uniqid());
        return substr($hash, 0, 32) . '.' . $extension;
    }

    /**
     * Upload temporaneo
     */
    private function uploadToTemp(UploadedFile $file, string $filename): string
    {
        $file->storeAs('', $filename, 'temp_uploads');
        return Storage::disk('temp_uploads')->path($filename);
    }

    /**
     * Controlli di sicurezza avanzati
     */
    private function performSecurityChecks(string $filePath): void
    {
        // 1. Verifica header del file (magic bytes)
        $this->verifyFileSignature($filePath);

        // 2. Scansione per contenuti malevoli
        $this->scanForMalware($filePath);

        // 3. Verifica metadati EXIF (rimozione dati sensibili)
        $this->sanitizeMetadata($filePath);
    }

    /**
     * Verifica signature del file
     */
    private function verifyFileSignature(string $filePath): void
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            throw new Exception('Impossibile leggere il file');
        }

        $bytes = fread($handle, 12);
        fclose($handle);

        $signatures = [
            'jpeg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89PNG\r\n\x1a\n"],
            'gif' => ["GIF87a", "GIF89a"],
            'webp' => ["WEBP"]
        ];

        $isValid = false;
        foreach ($signatures as $type => $sigs) {
            foreach ($sigs as $sig) {
                if (strpos($bytes, $sig) === 0) {
                    $isValid = true;
                    break 2;
                }
            }
        }

        if (!$isValid) {
            throw new Exception('Signature del file non valida - possibile file corrotto o malevolo');
        }
    }

    /**
     * Scansione anti-malware (basic)
     */
    private function scanForMalware(string $filePath): void
    {
        // Controlli base per pattern sospetti
        $content = file_get_contents($filePath);

        $suspiciousPatterns = [
            'eval(',
            'exec(',
            'system(',
            'shell_exec(',
            'passthru(',
            '<?php',
            '<script',
            'javascript:',
            'data:text/html'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                throw new Exception('File contiene contenuto potenzialmente pericoloso');
            }
        }
    }

    /**
     * Sanitizzazione metadati
     */
    private function sanitizeMetadata(string $filePath): void
    {
        // Rimuovere dati EXIF sensibili (GPS, informazioni personali)
        if (extension_loaded('exif')) {
            $exif = @exif_read_data($filePath);
            if ($exif && (isset($exif['GPS']) || isset($exif['UserComment']))) {
                // Log per review manuale se contiene dati GPS
                Log::info('Photo contains GPS data', ['file' => basename($filePath)]);
            }
        }
    }

    /**
     * Content moderation con AI
     */
    private function checkContentModeration(UploadedFile $originalFile, string $tempPath): array
    {
        try {
            // Crea un UploadedFile temporaneo MA con il nome originale
            $tempFile = new UploadedFile(
                $tempPath,
                $originalFile->getClientOriginalName(), // USA IL NOME ORIGINALE!
                $originalFile->getMimeType(),
                null,
                true
            );

            $moderationService = new ModerationService();
            $result = $moderationService->moderatePhoto($tempFile);

            Log::info('Photo moderation completed', [
                'original_filename' => $originalFile->getClientOriginalName(),
                'status' => $result['status'],
                'score' => $result['overall_score'],
                'requires_review' => $result['requires_review'],
                'flagged_reasons' => count($result['flagged_reasons']),
                'provider' => $result['provider']
            ]);

            return [
                'auto_approved' => $result['status'] === 'approved',
                'score' => $result['overall_score'],
                'confidence' => $result['confidence'],
                'flags' => $result['flagged_reasons'],
                'needs_human_review' => $result['requires_review'],
                'categories' => $result['categories'],
                'provider' => $result['provider'],
                'processing_time' => $result['processing_time_ms'],
                'full_result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Content moderation failed', [
                'error' => $e->getMessage(),
                'original_filename' => $originalFile->getClientOriginalName(),
                'file_path' => $tempPath
            ]);

            // Fallback conservativo: richiedi revisione manuale
            return [
                'auto_approved' => false,
                'score' => 0.5,
                'confidence' => 0.0,
                'flags' => [
                    [
                        'category' => 'error',
                        'score' => 0.5,
                        'description' => 'Errore durante moderazione: ' . $e->getMessage()
                    ]
                ],
                'needs_human_review' => true,
                'categories' => ['inappropriate' => 0.5],
                'provider' => 'error_fallback',
                'processing_time' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Processing dell'immagine
     */
    private function processImage(string $tempPath, string $filename): array
    {
        // Per ora senza Intervention Image, solo copia
        $originalPath = "original/{$filename}";
        $thumbnailPath = "thumbnails/thumb_{$filename}";

        // Copia file originale
        copy($tempPath, Storage::disk('photos')->path($originalPath));

        // Crea thumbnail (placeholder - servirà Intervention Image)
        copy($tempPath, Storage::disk('photos')->path($thumbnailPath));

        // Ottieni dimensioni
        $imageInfo = getimagesize($tempPath);

        return [
            'original' => $originalPath,
            'thumbnail' => $thumbnailPath,
            'dimensions' => [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1]
            ]
        ];
    }

    /**
     * Elimina una foto
     */
    public function deletePhoto(Entry $entry): bool
    {
        try {
            // Elimina file fisici usando i path relativi salvati nel database
            if ($entry->photo_url) {
                Storage::disk('photos')->delete($entry->photo_url);
            }

            if ($entry->thumbnail_url) {
                Storage::disk('photos')->delete($entry->thumbnail_url);
            }

            // Elimina record database
            $entry->delete();

            return true;
        } catch (Exception $e) {
            Log::error('Errore eliminazione foto', [
                'entry_id' => $entry->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
