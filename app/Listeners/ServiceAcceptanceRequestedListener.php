<?php

namespace App\Listeners;

use App\Events\ServiceAcceptanceRequested;
use App\Mail\ServiceAcceptanceRequestedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class ServiceAcceptanceRequestedListener
{
    public function handle(ServiceAcceptanceRequested $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.ServiceAcceptanceRequested::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new ServiceAcceptanceRequestedMail($event->project)
        );
    }
}
