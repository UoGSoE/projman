<?php

namespace App\Jobs;

use App\Mail\ProjectCreatedMail;
use App\Models\NotificationRule;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public NotificationRule $rule, public mixed $event) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // $recipients = $this->rule->recipients; // TODO: handle recipients such that if role/roles are given get users with that role, otherwise just take the users given as recipients
        // $users = isset($recipients['users']) ? User::whereIn('id', $recipients['users'])->get() : collect();
        // $roles = isset($recipients['roles']) ? Role::whereIn('id', $recipients['roles'])->get() : collect();
        // $usersByRoles = $roles ? User::whereHas('roles', function ($query) use ($roles) {
        //     $query->whereIn('id', $roles->pluck('id'));
        // })->get() : collect();
        // $allRecipients = $users->merge($usersByRoles)->unique('email');
        // $email = new ProjectCreatedMail($this->project);
        // Mail::to($allRecipients)->queue($email);
        // logger('Hello from Send email job');

        // to simulate failure, throw an exception
        // throw new \Exception('Test mail job exception');

        $recipients = $this->getRecipients();
        $mailable = $this->getMailableForEvent($this->rule->event, $this->event);

        if (! $mailable instanceof Mailable) {
            Log::warning('No mailable found for event: '.$this->rule->event);

            return;
        }

        if ($recipients->isEmpty()) {
            Log::warning('No recipients found for event: '.$this->rule->event);

            return;
        }

        foreach ($recipients as $recipient) {

            Mail::to($recipient->email)->queue($mailable);
        }
    }

    public function getRecipients()
    {
        $recipients = $this->rule->recipients;
        $users = isset($recipients['users']) ? User::whereIn('id', $recipients['users'])->get() : collect();
        $roles = isset($recipients['roles']) ? Role::whereIn('id', $recipients['roles'])->get() : collect();
        $usersByRoles = $roles->isNotEmpty() ? User::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('roles.id', $roles->pluck('id'));
        })->get() : collect();

        return $users->merge($usersByRoles)->unique('email');
    }

    public function getMailableForEvent($eventClass, $event)
    {
        $mapping = collect(config('notifiable_events', []))->firstWhere('class', $eventClass);

        if (! $mapping) {
            return null;
        }

        $mailableClass = $mapping['mailable'];

        $context = property_exists($event, 'project') ? $event->project : ($event->data ?? $event);

        return new $mailableClass($context);
    }
}
