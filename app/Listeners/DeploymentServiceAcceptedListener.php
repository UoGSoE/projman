<?php

namespace App\Listeners;

use App\Events\DeploymentServiceAccepted;
use App\Mail\DeploymentServiceAcceptedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class DeploymentServiceAcceptedListener
{
    public function handle(DeploymentServiceAccepted $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.DeploymentServiceAccepted::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new DeploymentServiceAcceptedMail($event->project)
        );
    }
}
