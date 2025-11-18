<?php

namespace App\Listeners;

use App\Events\SchedulingSubmittedToDCGG;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;

class SchedulingSubmittedToDCGGListener
{
    public function handle(SchedulingSubmittedToDCGG $event): void
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

    protected function resolveRecipients(SchedulingSubmittedToDCGG $event, array $config): array
    {
        $recipients = [];

        // Add role-based recipients
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

        // Add project owner if configured
        if (! empty($config['include_project_owner']) && $event->project->user) {
            $recipients[] = $event->project->user->email;
        }

        // Add DCGG email if configured
        if (! empty($config['include_dcgg_email'])) {
            $dcggEmail = config('projman.dcgg_email');
            if ($dcggEmail) {
                $recipients[] = $dcggEmail;
            }
        }

        return array_unique($recipients);
    }
}
