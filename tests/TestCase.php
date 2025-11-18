<?php

namespace Tests;

use App\Events\FeasibilityApproved;
use App\Events\FeasibilityRejected;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Events\SchedulingScheduled;
use App\Events\SchedulingSubmittedToDCGG;
use App\Events\ScopingSubmitted;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the base notification roles required for project lifecycle events.
     *
     * Call this method in tests that verify notification behavior or event dispatching.
     * Creates the roles configured in config/projman.php that notification listeners expect,
     * and assigns dummy users to those roles so notifications can be sent.
     */
    protected function setupBaseNotificationRoles(): void
    {
        // Required for ProjectCreated notifications
        $adminRole = Role::factory()->create(['name' => 'Admin']);
        $pmRole = Role::factory()->create(['name' => 'Project Manager']);

        // Required for Feasibility, Scoping, Scheduling notifications
        $assessorRole = Role::factory()->create(['name' => 'Work Package Assessor']);

        // Required for ProjectStageChange notifications
        Role::factory()->create(['name' => 'Ideation Manager']);
        Role::factory()->create(['name' => 'Feasibility Manager']);
        Role::factory()->create(['name' => 'Scoping Manager']);
        Role::factory()->create(['name' => 'Scheduling Manager']);
        Role::factory()->create(['name' => 'Detailed Design Manager']);
        Role::factory()->create(['name' => 'Development Manager']);
        $testingManagerRole = Role::factory()->create(['name' => 'Testing Manager']);
        $serviceLeadRole = Role::factory()->create(['name' => 'Service Lead']);
        Role::factory()->create(['name' => 'Deployment Manager']);
        Role::factory()->create(['name' => 'Completed Manager']);
        Role::factory()->create(['name' => 'Cancelled Manager']);

        // Create dummy users and assign to key roles
        // (prevents "No recipients found" exceptions)
        $adminUser = \App\Models\User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $assessorUser = \App\Models\User::factory()->create();
        $assessorUser->roles()->attach($assessorRole);
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
            $adminUser = \App\Models\User::factory()->create();
            $adminUser->roles()->attach($adminRole);
        }
    }
}
