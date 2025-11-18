<?php

namespace App\Listeners;

use App\Events\ScopingSubmitted;
use App\Models\Role;
use Illuminate\Support\Facades\Mail;

class ScopingSubmittedListener
{
    public function handle(ScopingSubmitted $event): void
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

    protected function resolveRecipients(ScopingSubmitted $event, array $config): array
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
}
