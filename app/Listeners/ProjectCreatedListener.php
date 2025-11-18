<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Mail\ProjectCreatedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class ProjectCreatedListener
{
    public function handle(ProjectCreated $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.ProjectCreated::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new ProjectCreatedMail($event->project)
        );
    }
}
