<?php

namespace App\Listeners;

use App\Jobs\SendEmailJob;
use App\Models\NotificationRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProjectEventsListener implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle($event): void
    {
        $eventClass = get_class($event);
        $rules = NotificationRule::where('event', $eventClass)->where('active', true)->get();

        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            SendEmailJob::dispatch($rule, $event);
        }
    }

    public function appliesTo($rule, $event)
    {
        $applies = $rule->applies_to ?? [];

        /**
         * If the rule applies to all projects, return true
         */
        if (in_array('all', $applies, true)) {
            return true;
        }

        /**
         * If the rule applies to a specific project, return true if the project is in the list
         */
        if (property_exists($event, 'project') && $event->project) {
            return in_array($event->project->id, $applies, true);
        }

        return false;
    }
}
