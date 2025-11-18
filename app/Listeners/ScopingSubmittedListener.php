<?php

namespace App\Listeners;

use App\Events\ScopingSubmitted;
use App\Mail\ScopingSubmittedMail;
use App\Services\RoleUserResolver;
use Illuminate\Support\Facades\Mail;

class ScopingSubmittedListener
{
    public function handle(ScopingSubmitted $event): void
    {
        $users = app(RoleUserResolver::class)->forEvent($event);

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'No recipients found for '.ScopingSubmitted::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($users->pluck('email'))->queue(
            new ScopingSubmittedMail($event->project)
        );
    }
}
