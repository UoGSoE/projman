<?php

namespace App\Jobs;

use App\Mail\ProjectCreatedMail;
use App\Models\NotificationRule;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public NotificationRule $rule, public Project $project) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $recipients = $this->rule->recipients; // TODO: handle recipients such that if role/roles are given get users with that role, otherwise just take the users given as recipients
        $users = isset($recipients['users']) ? User::whereIn('id', $recipients['users'])->get() : collect();
        $roles = isset($recipients['roles']) ? Role::whereIn('id', $recipients['roles'])->get() : collect();
        $usersByRoles = $roles ? User::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('id', $roles->pluck('id'));
        })->get() : collect();
        $allRecipients = $users->merge($usersByRoles)->unique('email');
        $email = new ProjectCreatedMail($this->project);
        Mail::to($allRecipients)->queue($email);
        logger('Hello from Send email job');

    }
}
