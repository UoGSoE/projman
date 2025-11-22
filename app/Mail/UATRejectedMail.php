<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UATRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'UAT Rejected: '.$this->project->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.uat_rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
