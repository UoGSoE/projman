<?php

use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Livewire\ProjectCreator;
use App\Mail\ProjectCreatedMail;
use App\Mail\ProjectStageChangeMail;
use App\Models\NotificationRule;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Form stage notifications', function () {

    beforeEach(function () {
        Mail::fake();

        $this->roles = Role::factory()->count(6)->create();
        $this->users = User::factory()->count(6)->create();
        $this->projects = Project::factory()->count(6)->create();

        $this->users->take(2)->each(fn ($user) => $user->roles()->attach($this->roles->first()));

        $this->projectCreatedRule = NotificationRule::factory()->create([
            'event' => ProjectCreated::class,
            'active' => true,
            'applies_to' => ['all'],
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        $this->projectStageChangeRule = NotificationRule::factory()->create([
            'event' => ProjectStageChange::class,
            'active' => true,
            'applies_to' => ['all'],
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);
    });

    it('sends a notification when a project is created', function () {
        $this->actingAs($this->users->first());

        livewire(ProjectCreator::class)
            ->set('projectName', 'Test Project')
            ->call('save')
            ->assertHasNoErrors();

        Mail::assertQueued(ProjectCreatedMail::class);

        // Check that emails were sent to the users specified in the notification rule
        foreach ($this->users as $user) {
            Mail::assertQueued(ProjectCreatedMail::class, function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            });
        }
    });

    // TODO: test content of the email

    it('sends a notification when a project advances to the next stage', function () {
        $project = Project::factory()->create();
        $newStatus = $project->advanceToNextStage();

        Mail::assertQueued(ProjectStageChangeMail::class);

        // Check that emails were sent to the users specified in the notification rule
        foreach ($this->users as $user) {
            Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) use ($user) {
                return $mail->hasTo($user->email);
            });
        }
    });
});
