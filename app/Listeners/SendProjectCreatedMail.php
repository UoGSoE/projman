<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Mail\ProjectCreatedMail;
use Illuminate\Support\Facades\Mail;

class SendProjectCreatedMail
{
    /**
     * Create the event listener.
     */
    public function __construct(public Project $project)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProjectCreated $event): void
    {
        foreach (config('projman.mail.project_created') as $email) {
            Mail::to($email)->queue(new ProjectCreatedMail($event->project));
        }
    }
}
