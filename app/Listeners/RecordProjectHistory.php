<?php

namespace App\Listeners;

use App\Events\ProjectUpdated;
use Illuminate\Support\Facades\Auth;

class RecordProjectHistory
{
    /**
     * Handle the event.
     */
    public function handle(ProjectUpdated $event): void
    {
        $event->project->addHistory(
            Auth::user(),
            $event->message
        );
    }
}
