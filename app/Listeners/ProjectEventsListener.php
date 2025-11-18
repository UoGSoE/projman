<?php

namespace App\Listeners;

use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;

class ProjectEventsListener
{
    public function handle(ProjectCreated|ProjectStageChange $event): void
    {
        $eventClass = get_class($event);
        $config = config('projman.notifications')[$eventClass] ?? null;

        if (! $config) {
            return;
        }

        if ($event instanceof ProjectStageChange) {
            $recipients = $this->resolveStageRecipients($event, $config);
        } else {
            $recipients = $this->resolveRecipients($event, $config);
        }

        if (empty($recipients)) {
            return;
        }

        $mailable = new ($config['mailable'])($event->project);

        Mail::to($recipients)->queue($mailable);
    }

    public function handleFeasibilityApproved(FeasibilityApproved $event): void
    {
        $this->sendNotification($event);
    }

    public function handleFeasibilityRejected(FeasibilityRejected $event): void
    {
        $this->sendNotification($event);
    }

    protected function sendNotification($event): void
    {
        $eventClass = get_class($event);
        $config = config('projman.notifications')[$eventClass] ?? null;

        if (! $config) {
            return;
        }

        $recipients = $this->resolveRecipients($event, $config);

        if (empty($recipients)) {
            return;
        }

        $mailable = new ($config['mailable'])($event->project);

        Mail::to($recipients)->queue($mailable);
    }

    protected function resolveRecipients($event, array $config): array
    {
        $recipients = [];

        if (! empty($config['roles'])) {
            $roleUsers = Role::whereIn('name', $config['roles'])
                ->with('users')
                ->get()
                ->pluck('users')
                ->flatten()
                ->pluck('email')
                ->unique()
                ->toArray();

            $recipients = array_merge($recipients, $roleUsers);
        }

        if (! empty($config['include_project_owner']) && $event->project->user) {
            $recipients[] = $event->project->user->email;
        }

        return array_unique($recipients);
    }

    protected function resolveStageRecipients(ProjectStageChange $event, array $config): array
    {
        $recipients = [];
        $currentStage = $event->project->status->value;

        if (! empty($config['stage_roles'][$currentStage])) {
            $stageRoles = $config['stage_roles'][$currentStage];
            $roleUsers = Role::whereIn('name', $stageRoles)
                ->with('users')
                ->get()
                ->pluck('users')
                ->flatten()
                ->pluck('email')
                ->unique()
                ->toArray();

            $recipients = array_merge($recipients, $roleUsers);
        }

        if (! empty($config['include_project_owner']) && $event->project->user) {
            $recipients[] = $event->project->user->email;
        }

        return array_unique($recipients);
    }
}
