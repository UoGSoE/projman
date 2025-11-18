<?php

namespace App\Listeners;

use App\Events\SchedulingSubmittedToDCGG;
use App\Mail\SchedulingSubmittedMail;
use Illuminate\Support\Facades\Mail;

class SchedulingSubmittedToDCGGListener
{
    public function handle(SchedulingSubmittedToDCGG $event): void
    {
        $email = config('projman.dcgg_email');

        if (! $email) {
            throw new \Exception('DCGG email not configured');
        }

        Mail::to($email)->queue(new SchedulingSubmittedMail($event->project));
    }
}
