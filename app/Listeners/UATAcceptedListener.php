<?php

namespace App\Listeners;

use App\Events\UATAccepted;
use App\Mail\UATAcceptedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class UATAcceptedListener
{
    public function handle(UATAccepted $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.UATAccepted::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new UATAcceptedMail($event->project)
        );
    }
}
