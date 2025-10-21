<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Jobs\SendEmailJob;
use App\Models\NotificationRule;
use Illuminate\Support\Facades\Log;

class ProjectEventsListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProjectCreated|ProjectStageChange $event): void
    {
        // logger('Hello from ProjectEventsListener');
        // logger($event);
        Log::info('Hello from ProjectEventsListener', ['event' => $event]);
        $eventClass = get_class($event);
        Log::info('Event class', ['eventClass' => $eventClass]);
        $rules = NotificationRule::where('event', $eventClass)->where('active', true)->get();
        Log::info('Rules', ['rules' => $rules]);
        if ($rules->isEmpty()) {
            Log::info('No rules found for event', ['event' => $event]);

            return;
        }

        foreach ($rules as $rule) {
            Log::info('Dispatching email job', ['rule' => $rule, 'event' => $event]);
            SendEmailJob::dispatch($rule, $event);
        }
    }
}
