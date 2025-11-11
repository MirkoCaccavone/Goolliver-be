<?php

namespace App\Mail;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PhotoRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Entry $entry;
    public User $user;
    public string $rejectionReason;
    public int $creditsAssigned;

    /**
     * Create a new message instance.
     */
    public function __construct(Entry $entry, string $rejectionReason, int $creditsAssigned)
    {
        $this->entry = $entry;
        $this->user = $entry->user;
        $this->rejectionReason = $rejectionReason;
        $this->creditsAssigned = $creditsAssigned;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📸 Foto Rifiutata - Credito Assegnato',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.photo-rejected',
            with: [
                'user' => $this->user,
                'entry' => $this->entry,
                'rejectionReason' => $this->rejectionReason,
                'creditsAssigned' => $this->creditsAssigned,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
