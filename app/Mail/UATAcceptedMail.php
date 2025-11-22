<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UATAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Project $project) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'UAT Accepted: '.$this->project->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.uat_accepted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
