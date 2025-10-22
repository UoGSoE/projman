<?php

use App\Jobs\SendEmailJob;
use App\Mail\ProjectCreatedMail;
use App\Models\NotificationRule;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Event::fake();

    $this->roles = Role::factory()->count(6)->create();
    $this->users = User::factory()->count(6)->create();
    $this->projects = Project::factory()->count(6)->create();
    $this->notificationRules = NotificationRule::factory()->count(6)->create();

    $this->users->take(2)->each(fn ($user) => $user->roles()->attach($this->roles->first()));
    $this->notificationRules->each(function ($rule) {
        $rule->recipients = [
            'users' => $this->users->pluck('id')->toArray(),
        ];
        $rule->save();
    });

});

it('sends email to users specified in notification rule', function () {
    $rule = NotificationRule::factory()->create([
        'event' => ['class' => 'project.created'],
        'recipients' => [
            'users' => $this->users->pluck('id')->toArray(),
        ],
    ]);
    $project = $this->projects->first();

    Config::set('notifiable_events', [
        ['class' => 'project.created', 'mailable' => ProjectCreatedMail::class],
    ]);

    $event = (object) ['project' => $project];
    $job = new SendEmailJob($rule, $event);

    $job->handle();

    foreach ($this->users as $user) {
        Mail::assertQueued(ProjectCreatedMail::class, fn ($mail) => $mail->hasTo($user->email));
    }
});

it('sends emails to users associated with roles', function () {
    $targetRole = $this->roles->first();
    $rule = NotificationRule::factory()->create([
        'event' => ['class' => 'project.created'],
        'recipients' => [
            'roles' => [$targetRole->id],
        ],
    ]);

    $project = Project::factory()->create();

    Config::set('notifiable_events', [
        ['class' => 'project.created', 'mailable' => ProjectCreatedMail::class],
    ]);

    $event = (object) ['project' => $project];
    $job = new SendEmailJob($rule, $event);

    $job->handle();

    $roleUsers = $this->users->filter(fn ($u) => $u->roles->contains($targetRole));
    // dd($roleUsers);
    foreach ($roleUsers as $user) {
        Mail::assertQueued(ProjectCreatedMail::class, fn ($mail) => $mail->hasTo($user->email));
    }
});

it('does not send emails when no recipients found', function () {
    $rule = NotificationRule::factory()->create([
        'event' => ['class' => 'project.created'],
        'recipients' => [],
    ]);

    $project = Project::factory()->create();

    Config::set('notifiable_events', [
        ['class' => 'project.created', 'mailable' => ProjectCreatedMail::class],
    ]);

    $event = (object) ['project' => $project];
    $job = new SendEmailJob($rule, $event);
    $job->handle();

    Mail::assertNothingQueued();
});

it('does not send emails when mailable is not found for event', function () {
    $rule = NotificationRule::factory()->create([
        'event' => ['class' => 'unknown.event'],
        'recipients' => ['users' => $this->users->pluck('id')->toArray()],
    ]);

    $event = (object) ['data' => 'sample'];
    Config::set('notifiable_events', []);

    $job = new SendEmailJob($rule, $event);
    $job->handle();

    Mail::assertNothingQueued();
});
