<?php

namespace App\Listeners;

use App\Events\FeasibilityApproved;
use App\Mail\FeasibilityApprovedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class FeasibilityApprovedListener
{
    public function handle(FeasibilityApproved $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.FeasibilityApproved::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new FeasibilityApprovedMail($event->project)
        );
    }
}
