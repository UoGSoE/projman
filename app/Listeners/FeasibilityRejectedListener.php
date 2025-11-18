<?php

namespace App\Listeners;

use App\Events\FeasibilityRejected;
use App\Mail\FeasibilityRejectedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class FeasibilityRejectedListener
{
    public function handle(FeasibilityRejected $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.FeasibilityRejected::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new FeasibilityRejectedMail($event->project)
        );
    }
}
