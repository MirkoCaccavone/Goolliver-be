<?php

namespace App\Exceptions;

use Exception;

class PhotoUploadException extends Exception
{
    protected $code = 'PHOTO_UPLOAD_ERROR';

    public function __construct(string $message = 'Photo upload failed', string $code = null, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        if ($code) {
            $this->code = $code;
        }
    }

    /**
     * Get the custom error code for the exception
     */
    public function getErrorCode(): string
    {
        return $this->code;
    }

    /**
     * Set the error code
     */
    public function setErrorCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Static factory methods for common errors
     */
    public static function invalidFile(string $reason = 'Invalid file format'): self
    {
        return new self($reason, 'INVALID_FILE');
    }

    public static function fileTooLarge(int $maxSize): self
    {
        return new self("File size exceeds {$maxSize}MB limit", 'FILE_TOO_LARGE');
    }

    public static function unsafeContent(string $reason = 'Content failed safety checks'): self
    {
        return new self($reason, 'UNSAFE_CONTENT');
    }

    public static function processingFailed(string $reason = 'Image processing failed'): self
    {
        return new self($reason, 'PROCESSING_FAILED');
    }

    public static function storageFailed(string $reason = 'Storage operation failed'): self
    {
        return new self($reason, 'STORAGE_FAILED');
    }

    public static function moderationFailed(string $reason = 'Content moderation failed'): self
    {
        return new self($reason, 'MODERATION_FAILED');
    }

    public static function malwareDetected(string $details = 'Malware detected in file'): self
    {
        return new self($details, 'MALWARE_DETECTED');
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render()
    {
        return response()->json([
            'error' => $this->getMessage(),
            'code' => $this->getErrorCode()
        ], $this->getStatusCode());
    }

    /**
     * Get appropriate HTTP status code for the error
     */
    private function getStatusCode(): int
    {
        switch ($this->code) {
            case 'INVALID_FILE':
            case 'FILE_TOO_LARGE':
            case 'UNSAFE_CONTENT':
                return 422;
            case 'MALWARE_DETECTED':
                return 403;
            case 'STORAGE_FAILED':
            case 'PROCESSING_FAILED':
            case 'MODERATION_FAILED':
                return 500;
            default:
                return 400;
        }
    }
}
