<?php

namespace App\Listeners;

use App\Events\DeploymentApproved;
use App\Mail\DeploymentApprovedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class DeploymentApprovedListener
{
    public function handle(DeploymentApproved $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.DeploymentApproved::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new DeploymentApprovedMail($event->project)
        );
    }
}
