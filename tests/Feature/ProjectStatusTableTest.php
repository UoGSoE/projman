<?php

use App\Enums\ProjectStatus;
use App\Livewire\ProjectStatusTable;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
});

describe('ProjectStatusTable userId tampering', function () {
    it('refuses to hydrate userId from the client', function () {
        $owner = User::factory()->requester()->create();
        $intruder = User::factory()->requester()->create();
        Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        livewire(ProjectStatusTable::class, ['userId' => $intruder->id])
            ->set('userId', $owner->id);
    });
});

describe('ProjectStatusTable cancelProject', function () {
    it('forbids a non-owner non-admin from cancelling a project', function () {
        $owner = User::factory()->requester()->create();
        $intruder = User::factory()->staff()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder);

        livewire(ProjectStatusTable::class)
            ->call('cancelProject', $project->id)
            ->assertForbidden();

        expect($project->fresh()->status)->not->toBe(ProjectStatus::CANCELLED);
    });

    it('allows the owner to cancel their own project', function () {
        $owner = User::factory()->requester()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner);

        livewire(ProjectStatusTable::class, ['userId' => $owner->id])
            ->call('cancelProject', $project->id)
            ->assertHasNoErrors();

        expect($project->fresh()->status)->toBe(ProjectStatus::CANCELLED);
    });

    it('allows an admin to cancel any project', function () {
        $owner = User::factory()->requester()->create();
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin);

        livewire(ProjectStatusTable::class)
            ->call('cancelProject', $project->id)
            ->assertHasNoErrors();

        expect($project->fresh()->status)->toBe(ProjectStatus::CANCELLED);
    });
});
