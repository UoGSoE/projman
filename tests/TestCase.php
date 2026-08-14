<?php

namespace Tests;

use App\Events\DeploymentApproved;
use App\Events\DeploymentServiceAccepted;
use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Events\ProjectUpdated;
use App\Events\SchedulingScheduled;
use App\Events\SchedulingSubmittedToDCGG;
use App\Events\ScopingSubmitted;
use App\Events\ServiceAcceptanceRequested;
use App\Events\UATAccepted;
use App\Events\UATRejected;
use App\Events\UATRequested;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\Traits\CreatesProjects;

abstract class TestCase extends BaseTestCase
{
    use CreatesProjects;

    /**
     * Mail is faked globally: with a sync queue, "queued" mail is otherwise
     * rendered for real (markdown + CSS inlining), which is slow. Tests that
     * need to prove a mailable renders should construct it and call render().
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /**
     * Set up the base notification roles required for project lifecycle events.
     *
     * Call this method in tests that verify notification behavior or event dispatching.
     * Creates the roles configured in config/projman.php that notification listeners expect,
     * and assigns dummy users to those roles so notifications can be sent.
     */
    protected function setupBaseNotificationRoles(): void
    {
        // Use the real seeder so test roles can't drift from production roles
        $this->seed(RoleSeeder::class);

        // Create dummy users and assign to key roles
        // (prevents "No recipients found" exceptions)
        foreach (['Admin', 'Work Package Assessor', 'Service Lead'] as $roleName) {
            $user = User::factory()->create([
                'forenames' => 'Test',
                'surname' => 'FakeNotificationsUser',
            ]);
            $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());
        }
    }

    /**
     * Fake notification events to prevent notification listeners from executing.
     *
     * Call this method in tests that don't verify notification behavior.
     * Prevents RuntimeException when no notification roles are set up.
     *
     * Note: Does NOT fake ProjectCreated because that's needed for CreateRelatedForms listener.
     * Instead, fakes specific notification events only.
     */
    protected function fakeNotifications(): void
    {
        // Fake only the notification-related listeners, not ProjectCreated
        // (ProjectCreated is needed for CreateRelatedForms to run)
        Event::fake([
            ProjectStageChange::class,
            FeasibilityApproved::class,
            FeasibilityRejected::class,
            ScopingSubmitted::class,
            SchedulingSubmittedToDCGG::class,
            SchedulingScheduled::class,
        ]);

        // For ProjectCreated, we need to set up minimal roles to prevent exceptions
        $this->ensureProjectCreatedRoles();
    }

    /**
     * Ensure the minimum roles exist for ProjectCreated notifications.
     *
     * Creates Admin and Project Manager roles with assigned users if they don't exist.
     * This is a lightweight version of setupBaseNotificationRoles() for tests that
     * fake notifications but still need ProjectCreated to work.
     */
    protected function ensureProjectCreatedRoles(): void
    {
        // Only create if they don't already exist
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $pmRole = Role::firstOrCreate(['name' => 'Project Manager']);

        // Ensure at least one user is assigned to avoid empty recipient errors
        if ($adminRole->users()->count() === 0) {
            $adminUser = User::factory()->create([
                'forenames' => 'Test',
                'surname' => 'FakeNotificationsUser',
            ]);
            $adminUser->roles()->attach($adminRole);
        }
    }

    /**
     * Fake ALL project-related events including ProjectCreated.
     *
     * Use this with createProject() for tests that don't need events at all.
     * This is the fastest option - no listeners run, no roles needed.
     */
    protected function fakeAllProjectEvents(): void
    {
        Event::fake([
            ProjectCreated::class,
            ProjectUpdated::class,
            ProjectStageChange::class,
            FeasibilityApproved::class,
            FeasibilityRejected::class,
            ScopingSubmitted::class,
            SchedulingSubmittedToDCGG::class,
            SchedulingScheduled::class,
            UATRequested::class,
            UATAccepted::class,
            UATRejected::class,
            ServiceAcceptanceRequested::class,
            DeploymentServiceAccepted::class,
            DeploymentApproved::class,
        ]);
    }
}
