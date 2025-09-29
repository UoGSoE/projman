<?php

namespace App\Listeners;

use App\Events\ProjectStageChange;
use App\Mail\ProjectStageChangeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendProjectStageChangeMail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProjectStageChange $event): void
    {
        $projectStatus = $event->project->status->value;
        $addresses = config('projman.mail.stages.' . $projectStatus);
        foreach ($addresses as $email) {
            Mail::to($email)->queue(new ProjectStageChangeMail($event->project));
        }
    }

    //TODO: dynamically handle email recipients for stage changes
}
