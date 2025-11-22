<?php

namespace App\Listeners;

use App\Events\UATRejected;
use App\Mail\UATRejectedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class UATRejectedListener
{
    public function handle(UATRejected $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.UATRejected::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new UATRejectedMail($event->project)
        );
    }
}
