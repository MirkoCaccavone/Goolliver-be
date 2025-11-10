<?php

namespace App\Exceptions;

use Exception;

class ModerationException extends Exception
{
    protected array $moderationResult;

    public function __construct(
        string $message = 'Moderation failed',
        int $code = 0,
        ?Exception $previous = null,
        array $moderationResult = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->moderationResult = $moderationResult;
    }

    /**
     * Get the moderation result data
     */
    public function getModerationResult(): array
    {
        return $this->moderationResult;
    }

    /**
     * Set the moderation result data
     */
    public function setModerationResult(array $result): void
    {
        $this->moderationResult = $result;
    }

    /**
     * Check if content was rejected
     */
    public function isContentRejected(): bool
    {
        return ($this->moderationResult['status'] ?? '') === 'rejected';
    }

    /**
     * Check if content requires manual review
     */
    public function requiresManualReview(): bool
    {
        return ($this->moderationResult['requires_review'] ?? false) === true;
    }

    /**
     * Get flagged reasons
     */
    public function getFlaggedReasons(): array
    {
        return $this->moderationResult['flagged_reasons'] ?? [];
    }

    /**
     * Get moderation score
     */
    public function getModerationScore(): float
    {
        return $this->moderationResult['overall_score'] ?? 0.0;
    }
}
