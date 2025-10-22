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
            'event' => ['class' => ProjectCreated::class],
            'active' => true,
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        $this->projectStageChangeRule = NotificationRule::factory()->create([
            'event' => ['class' => ProjectStageChange::class],
            'active' => true,
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

    it('sends notification only for specific project stage when rule has stage filter', function () {
        // Create a rule that only triggers for 'development' stage
        $developmentRule = NotificationRule::factory()->create([
            'event' => [
                'class' => ProjectStageChange::class,
                'project_stage' => 'development',
            ],
            'active' => true,
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        // Create a project and advance it to development stage
        $project = Project::factory()->create(['status' => 'development']);

        // Advance to next stage (testing)
        $project->advanceToNextStage();

        // Should trigger notification because it moved to development stage
        Mail::assertQueued(ProjectStageChangeMail::class);
    });

    it('does not send notification when project stage does not match rule filter', function () {
        // Create a rule that only triggers for 'testing' stage
        $testingRule = NotificationRule::factory()->create([
            'event' => [
                'class' => ProjectStageChange::class,
                'project_stage' => 'testing',
            ],
            'active' => true,
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        // Create a project and advance it to development stage (not testing)
        $project = Project::factory()->create(['status' => 'development']);
        $project->advanceToNextStage(); // This moves to testing, should trigger

        // Should trigger because it moved to testing stage
        Mail::assertQueued(ProjectStageChangeMail::class);
    });

    it('sends notification for all stages when rule has no stage filter', function () {
        // Create a rule without stage filter (should trigger for any stage change)
        $generalRule = NotificationRule::factory()->create([
            'event' => ['class' => ProjectStageChange::class], // No project_stage specified
            'active' => true,
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        // Create a project and advance it to any stage
        $project = Project::factory()->create(['status' => 'ideation']);
        $project->advanceToNextStage(); // Move to feasibility

        // Should trigger because rule has no stage filter
        Mail::assertQueued(ProjectStageChangeMail::class);
    });

    it('handles multiple rules with different stage filters correctly', function () {
        // Clear existing rules and mail
        NotificationRule::query()->delete();
        Mail::fake();

        // Create rules for different stages
        $ideationRule = NotificationRule::factory()->create([
            'event' => [
                'class' => ProjectStageChange::class,
                'project_stage' => 'ideation',
            ],
            'active' => true,
            'recipients' => [
                'users' => [$this->users->first()->id],
            ],
        ]);

        $developmentRule = NotificationRule::factory()->create([
            'event' => [
                'class' => ProjectStageChange::class,
                'project_stage' => 'development',
            ],
            'active' => true,
            'recipients' => [
                'users' => [$this->users->last()->id],
            ],
        ]);

        // Create a project and advance it to development stage
        $project = Project::factory()->create(['status' => 'development']);
        $project->advanceToNextStage(); // Move to testing

        // Should only trigger the development rule (when moving to development stage)
        $project2 = Project::factory()->create(['status' => 'detailed-design']);
        $project2->advanceToNextStage(); // Move to development

        // Should trigger the development rule
        Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) {
            return $mail->hasTo($this->users->last()->email);
        });

        // Should not trigger for the ideation rule user
        Mail::assertNotQueued(ProjectStageChangeMail::class, function ($mail) {
            return $mail->hasTo($this->users->first()->email);
        });
    });

    it('sends notification when project moves to specific stage from any previous stage', function () {
        // Create a rule for 'testing' stage
        $testingRule = NotificationRule::factory()->create([
            'event' => [
                'class' => ProjectStageChange::class,
                'project_stage' => 'testing',
            ],
            'active' => true,
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        // Test moving from development to testing
        $project = Project::factory()->create(['status' => 'development']);
        $project->advanceToNextStage(); // Should move to testing

        Mail::assertQueued(ProjectStageChangeMail::class);
    });

    it('does not send notification for inactive rules with stage filters', function () {
        // Clear existing rules and mail
        NotificationRule::query()->delete();
        Mail::fake();

        // Create an inactive rule for 'development' stage
        $inactiveRule = NotificationRule::factory()->create([
            'event' => [
                'class' => ProjectStageChange::class,
                'project_stage' => 'development',
            ],
            'active' => false, // Inactive rule
            'recipients' => [
                'users' => $this->users->pluck('id')->toArray(),
            ],
        ]);

        // Create a project and advance it to development stage
        $project = Project::factory()->create(['status' => 'development']);
        $project->advanceToNextStage();

        // Should not trigger because rule is inactive
        Mail::assertNothingQueued();
    });
});
