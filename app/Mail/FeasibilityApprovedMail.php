<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeasibilityApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(protected Project $project) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Feasibility Approved: '.$this->project->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feasibility_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
