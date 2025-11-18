<?php

namespace App\Listeners;

use App\Events\ProjectStageChange;
use App\Mail\ProjectStageChangeMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class ProjectStageChangeListener
{
    public function handle(ProjectStageChange $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.ProjectStageChange::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new ProjectStageChangeMail($event->project)
        );
    }
}
