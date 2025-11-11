<?php

namespace App\Listeners;

use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Jobs\SendEmailJob;
use App\Models\NotificationRule;

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
        $eventClass = get_class($event);
        $rules = NotificationRule::where('event->class', $eventClass)->where('active', true)->get();

        if ($event instanceof ProjectStageChange) {
            $currentStage = $event->project->status->value;
            $rules = $rules->filter(function ($rule) use ($currentStage) {
                $eventData = $rule->event;
                if (! isset($eventData['project_stage'])) {
                    return true;
                }

                return $eventData['project_stage'] === $currentStage;
            });
        }

        if ($rules->isEmpty()) {

            return;
        }

        foreach ($rules as $rule) {
            SendEmailJob::dispatch($rule, $event);
        }
    }

    public function handleFeasibilityApproved(FeasibilityApproved $event): void
    {
        $eventClass = get_class($event);
        $rules = NotificationRule::where('event->class', $eventClass)->where('active', true)->get();

        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            SendEmailJob::dispatch($rule, $event);
        }
    }

    public function handleFeasibilityRejected(FeasibilityRejected $event): void
    {
        $eventClass = get_class($event);
        $rules = NotificationRule::where('event->class', $eventClass)->where('active', true)->get();

        if ($rules->isEmpty()) {
            return;
        }

        foreach ($rules as $rule) {
            SendEmailJob::dispatch($rule, $event);
        }
    }
}
