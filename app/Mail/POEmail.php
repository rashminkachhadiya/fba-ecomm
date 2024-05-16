<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class POEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
  
    /**
     * Create a new message instance.
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: is_null($this->mailData['subject']) ? '' :$this->mailData['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase_order',
            with: [
                'body_message' => is_null($this->mailData['message']) ? '' :$this->mailData['message'],
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
        return [
            Attachment::fromStorageDisk('public', $this->mailData['attachment']),
        ];
    }
}
