<?php

namespace App\Listeners;

use App\Events\UATRequested;
use App\Mail\UATRequestedMail;
use Illuminate\Support\Facades\Mail;

class UATRequestedListener
{
    public function handle(UATRequested $event): void
    {
        $uatTester = $event->project->testing->uatTester;

        if (! $uatTester) {
            throw new \RuntimeException(
                'No UAT Tester assigned for '.UATRequested::class.
                ' notification (Project #'.$event->project->id.')'
            );
        }

        Mail::to($uatTester->email)->queue(
            new UATRequestedMail($event->project)
        );
    }
}
