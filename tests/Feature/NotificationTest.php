<?php

use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Events\SchedulingScheduled;
use App\Events\SchedulingSubmittedToDCGG;
use App\Events\ScopingSubmitted;
use App\Livewire\ProjectCreator;
use App\Mail\FeasibilityApprovedMail;
use App\Mail\FeasibilityRejectedMail;
use App\Mail\ProjectCreatedMail;
use App\Mail\ProjectStageChangeMail;
use App\Mail\SchedulingScheduledMail;
use App\Mail\SchedulingSubmittedMail;
use App\Mail\ScopingSubmittedMail;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Config-based notifications', function () {

    beforeEach(function () {
        // Create roles configured in projman.notifications
        $this->adminRole = Role::factory()->create(['name' => 'Admin']);
        $this->projectManagerRole = Role::factory()->create(['name' => 'Project Manager']);
        $this->assessorRole = Role::factory()->create(['name' => 'Work Package Assessor']);
        $this->scopingManagerRole = Role::factory()->create(['name' => 'Scoping Manager']);
        $this->testingManagerRole = Role::factory()->create(['name' => 'Testing Manager']);
        $this->serviceLeadRole = Role::factory()->create(['name' => 'Service Lead']);

        // Create users and assign roles
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);

        $this->projectManager = User::factory()->create();
        $this->projectManager->roles()->attach($this->projectManagerRole);

        $this->assessor = User::factory()->create();
        $this->assessor->roles()->attach($this->assessorRole);

        $this->projectOwner = User::factory()->create();
    });

    it('renders the project title and requester name in the ProjectCreated email body', function () {
        Mail::fake();

        $project = Project::factory()->create([
            'title' => 'A very specific project title',
            'user_id' => $this->projectOwner->id,
        ]);

        $rendered = (new ProjectCreatedMail($project))->render();

        expect($rendered)->toContain('A very specific project title');
        expect($rendered)->toContain($this->projectOwner->name);
    });

    it('sends ProjectCreated notification to configured roles', function () {
        Mail::fake();

        $this->actingAs($this->projectOwner);

        livewire(ProjectCreator::class)
            ->set('projectName', 'Test Project')
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::where('title', 'Test Project')->firstOrFail();

        // Exactly one mail, carrying this project, to exactly the Admin and Project Manager
        Mail::assertQueued(ProjectCreatedMail::class, 1);
        Mail::assertQueued(ProjectCreatedMail::class, function ($mail) use ($project) {
            return $mail->project->is($project)
                && $mail->hasTo($this->admin->email)
                && $mail->hasTo($this->projectManager->email)
                && count($mail->to) === 2;
        });

        // Should NOT include project owner (config has include_project_owner = false)
        Mail::assertNotQueued(ProjectCreatedMail::class, function ($mail) {
            return $mail->hasTo($this->projectOwner->email);
        });
    });

    it('sends FeasibilityApproved notification to Work Package Assessor role', function () {
        Mail::fake();

        $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

        event(new FeasibilityApproved($project));

        Mail::assertQueued(FeasibilityApprovedMail::class, 1);

        Mail::assertQueued(FeasibilityApprovedMail::class, function ($mail) use ($project) {
            return $mail->hasTo($this->assessor->email) && $mail->project->is($project);
        });

        // Should NOT include project owner
        Mail::assertNotQueued(FeasibilityApprovedMail::class, function ($mail) {
            return $mail->hasTo($this->projectOwner->email);
        });
    });

    it('sends FeasibilityRejected notification to Work Package Assessor and project owner', function () {
        Mail::fake();

        $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

        event(new FeasibilityRejected($project));

        Mail::assertQueued(FeasibilityRejectedMail::class, 1);

        Mail::assertQueued(FeasibilityRejectedMail::class, function ($mail) use ($project) {
            return $mail->hasTo($this->assessor->email) && $mail->project->is($project);
        });

        // SHOULD include project owner (config has include_project_owner = true)
        Mail::assertQueued(FeasibilityRejectedMail::class, function ($mail) {
            return $mail->hasTo($this->projectOwner->email);
        });
    });

    it('sends ScopingSubmitted notification to Work Package Assessor role', function () {
        Mail::fake();

        $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

        event(new ScopingSubmitted($project));

        Mail::assertQueued(ScopingSubmittedMail::class, 1);

        Mail::assertQueued(ScopingSubmittedMail::class, function ($mail) use ($project) {
            return $mail->hasTo($this->assessor->email) && $mail->project->is($project);
        });

        // Should NOT include project owner (config has include_project_owner = false)
        Mail::assertNotQueued(ScopingSubmittedMail::class, function ($mail) {
            return $mail->hasTo($this->projectOwner->email);
        });
    });

    it('sends SchedulingSubmittedToDCGG notification to configured email address', function () {
        Mail::fake();

        config(['projman.dcgg_email' => 'dcgg@example.ac.uk']);

        $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

        event(new SchedulingSubmittedToDCGG($project));

        Mail::assertQueued(SchedulingSubmittedMail::class, 1);

        Mail::assertQueued(SchedulingSubmittedMail::class, function ($mail) use ($project) {
            return $mail->hasTo('dcgg@example.ac.uk') && $mail->project->is($project);
        });
    });

    it('sends SchedulingScheduled notification to configured roles', function () {
        Mail::fake();

        $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

        event(new SchedulingScheduled($project));

        Mail::assertQueued(SchedulingScheduledMail::class, 1);

        Mail::assertQueued(SchedulingScheduledMail::class, function ($mail) use ($project) {
            return $mail->hasTo($this->assessor->email) && $mail->project->is($project);
        });

        // Should NOT include project owner
        Mail::assertNotQueued(SchedulingScheduledMail::class, function ($mail) {
            return $mail->hasTo($this->projectOwner->email);
        });
    });

    it('sends ProjectStageChange notification to stage-specific roles', function () {
        Mail::fake();

        $testingManager = User::factory()->create();
        $testingManager->roles()->attach($this->testingManagerRole);

        $serviceLead = User::factory()->create();
        $serviceLead->roles()->attach($this->serviceLeadRole);

        $project = Project::factory()->create([
            'user_id' => $this->projectOwner->id,
            'status' => 'testing',
        ]);

        event(new ProjectStageChange($project));

        Mail::assertQueued(ProjectStageChangeMail::class, 1);

        // Testing stage should notify Testing Manager and Service Lead
        Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) use ($testingManager, $project) {
            return $mail->hasTo($testingManager->email) && $mail->project->is($project);
        });

        Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) use ($serviceLead) {
            return $mail->hasTo($serviceLead->email);
        });

        // Should include project owner
        Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) {
            return $mail->hasTo($this->projectOwner->email);
        });
    });

    it('throws exception when no users have configured roles', function () {
        // Remove all role assignments
        $this->admin->roles()->detach();
        $this->projectManager->roles()->detach();
        $this->assessor->roles()->detach();

        // Fake ProjectCreated event to prevent its listener from throwing during project creation
        Event::fake([ProjectCreated::class]);

        $project = Project::factory()->create(['user_id' => $this->projectOwner->id]);

        // Now test that FeasibilityApproved throws when there are no recipients
        expect(fn () => event(new FeasibilityApproved($project)))
            ->toThrow(RuntimeException::class, 'No recipients found');
    });

    it('sends notification only to users with the correct role', function () {
        Mail::fake();

        // Create Feasibility Manager role and a user with that role
        $feasibilityManagerRole = Role::factory()->create(['name' => 'Feasibility Manager']);
        $feasibilityManager = User::factory()->create();
        $feasibilityManager->roles()->attach($feasibilityManagerRole);

        // Create a user WITHOUT the Feasibility Manager role
        $randomUser = User::factory()->create();

        $project = Project::factory()->create([
            'user_id' => $this->projectOwner->id,
            'status' => 'ideation',
        ]);

        $project->advanceToNextStage(); // Move to feasibility

        // Assert mail was queued only once (not to every user in the system)
        Mail::assertQueued(ProjectStageChangeMail::class, 1);

        // Should send to the Feasibility Manager
        Mail::assertQueued(ProjectStageChangeMail::class, function ($mail) use ($feasibilityManager, $project) {
            return $mail->hasTo($feasibilityManager->email) && $mail->project->is($project);
        });

        // Should NOT send to the random user without the role
        Mail::assertNotQueued(ProjectStageChangeMail::class, function ($mail) use ($randomUser) {
            return $mail->hasTo($randomUser->email);
        });
    });
});
