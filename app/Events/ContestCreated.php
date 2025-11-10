<?php

namespace App\Events;

use App\Models\Contest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContestCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Il concorso creato
     */
    public Contest $contest;

    /**
     * Create a new event instance.
     */
    public function __construct(Contest $contest)
    {
        $this->contest = $contest;
    }
}
