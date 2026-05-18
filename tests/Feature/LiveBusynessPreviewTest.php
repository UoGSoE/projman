<?php

use App\Enums\AvailabilityForChange;
use App\Enums\Busyness;
use App\Enums\EffortScale;
use App\Enums\ProjectStatus;
use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fakeNotifications();
    $this->admin = User::factory()->create(['is_admin' => true, 'is_staff' => true]);
    $this->actingAs($this->admin);
});

describe('Busyness::fromProjectCount()', function () {
    it('returns UNKNOWN for 0 projects', function () {
        expect(Busyness::fromProjectCount(0))->toBe(Busyness::UNKNOWN);
    });

    it('returns LOW for 1-2 projects', function () {
        expect(Busyness::fromProjectCount(1))->toBe(Busyness::LOW);
        expect(Busyness::fromProjectCount(2))->toBe(Busyness::LOW);
    });

    it('returns MEDIUM for 3-4 projects', function () {
        expect(Busyness::fromProjectCount(3))->toBe(Busyness::MEDIUM);
        expect(Busyness::fromProjectCount(4))->toBe(Busyness::MEDIUM);
    });

    it('returns HIGH for 5+ projects', function () {
        expect(Busyness::fromProjectCount(5))->toBe(Busyness::HIGH);
        expect(Busyness::fromProjectCount(10))->toBe(Busyness::HIGH);
        expect(Busyness::fromProjectCount(100))->toBe(Busyness::HIGH);
    });
});

describe('Busyness::adjustedBy()', function () {
    it('increases LOW to MEDIUM with +1', function () {
        expect(Busyness::LOW->adjustedBy(1))->toBe(Busyness::MEDIUM);
    });

    it('increases MEDIUM to HIGH with +1', function () {
        expect(Busyness::MEDIUM->adjustedBy(1))->toBe(Busyness::HIGH);
    });

    it('keeps HIGH at HIGH with +1 (cannot exceed)', function () {
        expect(Busyness::HIGH->adjustedBy(1))->toBe(Busyness::HIGH);
    });

    it('decreases HIGH to MEDIUM with -1', function () {
        expect(Busyness::HIGH->adjustedBy(-1))->toBe(Busyness::MEDIUM);
    });

    it('decreases MEDIUM to LOW with -1', function () {
        expect(Busyness::MEDIUM->adjustedBy(-1))->toBe(Busyness::LOW);
    });

    it('keeps LOW at LOW with -1 (cannot go below)', function () {
        expect(Busyness::LOW->adjustedBy(-1))->toBe(Busyness::LOW);
    });

    it('returns LOW when UNKNOWN gets +1 (now has work)', function () {
        expect(Busyness::UNKNOWN->adjustedBy(1))->toBe(Busyness::LOW);
    });

    it('keeps UNKNOWN as UNKNOWN with -1', function () {
        expect(Busyness::UNKNOWN->adjustedBy(-1))->toBe(Busyness::UNKNOWN);
    });

    it('handles larger adjustments correctly', function () {
        expect(Busyness::LOW->adjustedBy(2))->toBe(Busyness::HIGH);
        expect(Busyness::HIGH->adjustedBy(-2))->toBe(Busyness::LOW);
    });
});

describe('User::activeAssignedProjectCount()', function () {
    it('counts projects where user is assigned_to', function () {
        $user = User::factory()->create(['is_staff' => true]);

        // Create 2 active projects assigned to this user
        $project1 = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project1->scheduling->update(['assigned_to' => $user->id]);

        $project2 = Project::factory()->create(['status' => ProjectStatus::DEVELOPMENT]);
        $project2->scheduling->update(['assigned_to' => $user->id]);

        expect($user->activeAssignedProjectCount())->toBe(2);
    });

    it('counts projects where user is technical_lead', function () {
        $user = User::factory()->create(['is_staff' => true]);

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scheduling->update(['technical_lead_id' => $user->id]);

        expect($user->activeAssignedProjectCount())->toBe(1);
    });

    it('counts projects where user is change_champion', function () {
        $user = User::factory()->create(['is_staff' => true]);

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scheduling->update(['change_champion_id' => $user->id]);

        expect($user->activeAssignedProjectCount())->toBe(1);
    });

    it('counts projects where user is in cose_it_staff', function () {
        $user = User::factory()->create(['is_staff' => true]);

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scheduling->update(['cose_it_staff' => [$user->id]]);

        expect($user->activeAssignedProjectCount())->toBe(1);
    });

    it('excludes completed projects', function () {
        $user = User::factory()->create(['is_staff' => true]);

        $activeProject = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $activeProject->scheduling->update(['assigned_to' => $user->id]);

        $completedProject = Project::factory()->create(['status' => ProjectStatus::COMPLETED]);
        $completedProject->scheduling->update(['assigned_to' => $user->id]);

        expect($user->activeAssignedProjectCount())->toBe(1);
    });

    it('excludes cancelled projects', function () {
        $user = User::factory()->create(['is_staff' => true]);

        $activeProject = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $activeProject->scheduling->update(['assigned_to' => $user->id]);

        $cancelledProject = Project::factory()->create(['status' => ProjectStatus::CANCELLED]);
        $cancelledProject->scheduling->update(['assigned_to' => $user->id]);

        expect($user->activeAssignedProjectCount())->toBe(1);
    });

    it('does not double count user assigned in multiple roles on same project', function () {
        $user = User::factory()->create(['is_staff' => true]);

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scheduling->update([
            'assigned_to' => $user->id,
            'technical_lead_id' => $user->id,
            'change_champion_id' => $user->id,
            'cose_it_staff' => [$user->id],
        ]);

        // Same project should only count once
        expect($user->activeAssignedProjectCount())->toBe(1);
    });
});

describe('ProjectEditor live preview (NEW model — TODO)', function () {
    // The previous tests asserted busyness ±1 adjustments on the old enum model.
    // The new model needs different live-preview behaviour: when the user
    // toggles staff in the scheduling form, the heatmap cells for those users
    // should add/remove the in-edit project's per-day cost. To implement that,
    // ProjectEditor needs to pass the form's current effort/dates/staff into
    // the cell calculation as a "preview override" (since they aren't saved
    // yet). Tests will be reinstated once that path is built.

    it('stores original assigned staff IDs on mount', function () {
        $staffMember = User::factory()->create(['is_staff' => true]);

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scheduling->update(['assigned_to' => $staffMember->id]);

        $component = Livewire::test(ProjectEditor::class, ['project' => $project]);

        expect($component->get('originalAssignedStaffIds'))->toContain($staffMember->id);
    });

    it('returns empty array for new project with no saved staff', function () {
        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);

        $component = Livewire::test(ProjectEditor::class, ['project' => $project]);

        expect($component->get('originalAssignedStaffIds'))->toBe([]);
    });

    it('reflects newly selected staff in the heatmap (live preview)', function () {
        // A staff member with Moderate (60%) AFC and no committed projects.
        $alice = User::factory()->create([
            'is_staff' => true,
            'availability_for_change' => AvailabilityForChange::Moderate,
        ]);

        // A scheduling-stage project, scoped Small (5 person-days), 5-weekday duration.
        // If Alice is allocated solo: 5 / 1 / 5 / 0.6 = 1.667 → 167% → Black.
        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scoping->update(['estimated_effort' => EffortScale::SMALL]);

        $start = Carbon::today()->isWeekend()
            ? Carbon::today()->next(Carbon::MONDAY)
            : Carbon::today();

        $component = Livewire::test(ProjectEditor::class, ['project' => $project])
            ->set('scopingForm.estimatedEffort', EffortScale::SMALL)
            ->set('schedulingForm.estimatedStartDate', $start->format('Y-m-d'))
            ->set('schedulingForm.estimatedCompletionDate', $start->copy()->addWeekdays(4)->format('Y-m-d'))
            ->set('schedulingForm.assignedTo', $alice->id);

        $heatmapData = $component->get('heatmapData');
        $aliceEntry = collect($heatmapData['staff'])->firstWhere('user.id', $alice->id);

        // The first cell should reflect the in-edit project's projected impact.
        expect($aliceEntry['cells'][0]->colour())->toBe('bg-black');
    });

    it('removes deselected staff contribution from the heatmap (live preview)', function () {
        // Alice is saved as the assignee on a Small/5-weekday project — her saved
        // cell would be Black (1.667 utilisation). When the editor deselects her,
        // her cell should fall back to Green (no projects).
        $alice = User::factory()->create([
            'is_staff' => true,
            'availability_for_change' => AvailabilityForChange::Moderate,
        ]);

        $start = Carbon::today()->isWeekend()
            ? Carbon::today()->next(Carbon::MONDAY)
            : Carbon::today();

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scoping->update(['estimated_effort' => EffortScale::SMALL]);
        $project->scheduling->update([
            'assigned_to' => $alice->id,
            'estimated_start_date' => $start,
            'estimated_completion_date' => $start->copy()->addWeekdays(4),
        ]);

        $component = Livewire::test(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.assignedTo', null);

        $heatmapData = $component->get('heatmapData');
        $aliceEntry = collect($heatmapData['staff'])->firstWhere('user.id', $alice->id);

        expect($aliceEntry['cells'][0]->colour())->toBe('bg-green-500');
    });
    it('surfaces a notice in the heatmap when the in-edit project lacks the data needed to model it', function () {
        // Project without scoping effort or scheduling dates filled in.
        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);

        Livewire::test(ProjectEditor::class, ['project' => $project])
            ->set('showHeatmap', true)
            ->assertSee('Set the start and completion dates');
    });

    it('shows saved cost for staff whose selection has not changed (live preview)', function () {
        // Mount the editor with no form changes. The base cells exclude the
        // in-edit project; the live preview re-adds its cost based on the form's
        // current state. With no changes, that state matches the saved state,
        // so the cell should look the same as it would if the editor never
        // touched it.
        $alice = User::factory()->create([
            'is_staff' => true,
            'availability_for_change' => AvailabilityForChange::Moderate,
        ]);

        $start = Carbon::today()->isWeekend()
            ? Carbon::today()->next(Carbon::MONDAY)
            : Carbon::today();

        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);
        $project->scoping->update(['estimated_effort' => EffortScale::SMALL]);
        $project->scheduling->update([
            'assigned_to' => $alice->id,
            'estimated_start_date' => $start,
            'estimated_completion_date' => $start->copy()->addWeekdays(4),
        ]);

        $component = Livewire::test(ProjectEditor::class, ['project' => $project]);

        $heatmapData = $component->get('heatmapData');
        $aliceEntry = collect($heatmapData['staff'])->firstWhere('user.id', $alice->id);

        // 5 / 1 / 5 / 0.6 = 1.667 → Black, same as if the editor weren't involved.
        expect($aliceEntry['cells'][0]->colour())->toBe('bg-black');
    });
});

describe('Heatmap reactivity', function () {
    it('updates heatmap when staff selection changes', function () {
        $staffMember = User::factory()->create(['is_staff' => true]);
        $project = Project::factory()->create(['status' => ProjectStatus::SCHEDULING]);

        $component = Livewire::test(ProjectEditor::class, ['project' => $project])
            ->set('showHeatmap', true);

        // Initially no assigned staff
        $heatmapData = $component->get('heatmapData');
        expect($heatmapData['hasAssignedStaff'])->toBeFalse();

        // Select a staff member
        $component->set('schedulingForm.assignedTo', $staffMember->id);

        // Heatmap should update
        $heatmapData = $component->get('heatmapData');
        expect($heatmapData['hasAssignedStaff'])->toBeTrue();
    });
});
