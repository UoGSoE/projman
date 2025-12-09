<?php

use App\Livewire\ProjectEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('Scheduling Heatmap Integration', function () {
    beforeEach(function () {
        // Fake all events and use createProject() for faster tests
        $this->fakeAllProjectEvents();
    });

    it('displays heatmap when Model button is clicked', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();
        $this->actingAs($user);

        // Act & Assert - initially not visible
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->assertDontSeeHtml('data-test="heatmap-grid"');

        // Act - click Model button
        $component->call('toggleHeatmap')
            ->assertSet('showHeatmap', true)
            ->assertSeeHtml('data-test="heatmap-grid"');
    });

    it('hides heatmap when Model button is clicked again', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();
        $this->actingAs($user);

        // Act & Assert - toggle on then off
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('toggleHeatmap')
            ->assertSet('showHeatmap', true)
            ->call('toggleHeatmap')
            ->assertSet('showHeatmap', false)
            ->assertDontSeeHtml('data-test="heatmap-grid"');
    });

    it('shows assigned staff at top of heatmap when staff are assigned', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();

        // Create staff members with known surnames for ordering
        $assignedStaff = User::factory()->create([
            'surname' => 'Zebra',
            'forenames' => 'Alice',
            'is_staff' => true,
        ]);
        $unassignedStaff = User::factory()->create([
            'surname' => 'Apple',
            'forenames' => 'Bob',
            'is_staff' => true,
        ]);

        $this->actingAs($user);

        // Act - assign staff and show heatmap
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.assignedTo', $assignedStaff->id)
            ->call('toggleHeatmap');

        // Assert - assigned staff (Zebra) should appear before unassigned (Apple) despite alphabetical order
        $heatmapData = $component->get('heatmapData');

        expect($heatmapData['hasAssignedStaff'])->toBeTrue();

        $staffCollection = $heatmapData['staff'];
        $firstStaff = $staffCollection->first()['user'];
        $secondStaff = $staffCollection->skip(1)->first()['user'];

        expect($firstStaff->id)->toBe($assignedStaff->id)
            ->and($secondStaff->id)->toBe($unassignedStaff->id);
    });

    it('shows all staff alphabetically when no staff are assigned', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true, 'surname' => 'TestAdmin']);
        $project = $this->createProject();

        // Create staff with known surnames
        $staffA = User::factory()->create([
            'surname' => 'Alpha',
            'forenames' => 'Test',
            'is_staff' => true,
        ]);
        $staffZ = User::factory()->create([
            'surname' => 'Zulu',
            'forenames' => 'Test',
            'is_staff' => true,
        ]);

        $this->actingAs($user);

        // Act - show heatmap without assigning anyone
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->call('toggleHeatmap');

        // Assert - should be alphabetical and no assigned staff flag
        $heatmapData = $component->get('heatmapData');

        expect($heatmapData['hasAssignedStaff'])->toBeFalse();

        $staffCollection = $heatmapData['staff'];
        $firstStaff = $staffCollection->first()['user'];
        $lastStaff = $staffCollection->last()['user'];

        expect($firstStaff->surname)->toBe('Alpha')
            ->and($lastStaff->surname)->toBe('Zulu');
    });

    it('includes technical lead and change champion in assigned staff', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();

        $techLead = User::factory()->create(['is_staff' => true, 'surname' => 'TechLead']);
        $changeChampion = User::factory()->create(['is_staff' => true, 'surname' => 'Champion']);
        $unassigned = User::factory()->create(['is_staff' => true, 'surname' => 'Aardvark']);

        $this->actingAs($user);

        // Act
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.technicalLeadId', $techLead->id)
            ->set('schedulingForm.changeChampionId', $changeChampion->id)
            ->call('toggleHeatmap');

        // Assert - tech lead and champion should appear before unassigned
        $heatmapData = $component->get('heatmapData');
        $staffCollection = $heatmapData['staff'];

        $assignedStaffIds = $staffCollection->take(2)->pluck('user.id')->all();

        expect($assignedStaffIds)->toContain($techLead->id)
            ->and($assignedStaffIds)->toContain($changeChampion->id)
            ->and($staffCollection->skip(2)->first()['user']->id)->toBe($unassigned->id);
    });

    it('includes CoSE IT staff in assigned staff list', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();

        $coseStaff1 = User::factory()->create(['is_staff' => true, 'surname' => 'Staff1']);
        $coseStaff2 = User::factory()->create(['is_staff' => true, 'surname' => 'Staff2']);
        $unassigned = User::factory()->create(['is_staff' => true, 'surname' => 'Aardvark']);

        $this->actingAs($user);

        // Act
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.coseItStaff', [$coseStaff1->id, $coseStaff2->id])
            ->call('toggleHeatmap');

        // Assert
        $heatmapData = $component->get('heatmapData');
        $staffCollection = $heatmapData['staff'];

        $assignedStaffIds = $staffCollection->take(2)->pluck('user.id')->all();

        expect($assignedStaffIds)->toContain($coseStaff1->id)
            ->and($assignedStaffIds)->toContain($coseStaff2->id);
    });

    it('shows both assigned_to and coseItStaff together at top of heatmap', function () {
        // Arrange - This tests the exact user scenario: Joe (assigned_to) + Jenny (coseItStaff)
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();

        // Joe Blogs - assigned_to
        $joeBlogs = User::factory()->create([
            'surname' => 'Blogs',
            'forenames' => 'Joe',
            'is_staff' => true,
        ]);

        // Jenny Smith - in coseItStaff
        $jennySmith = User::factory()->create([
            'surname' => 'Smith',
            'forenames' => 'Jenny',
            'is_staff' => true,
        ]);

        // Unassigned staff with surnames that would sort BEFORE Joe and Jenny alphabetically
        $alice = User::factory()->create(['surname' => 'Anderson', 'forenames' => 'Alice', 'is_staff' => true]);
        $bob = User::factory()->create(['surname' => 'Baker', 'forenames' => 'Bob', 'is_staff' => true]);
        $charlie = User::factory()->create(['surname' => 'Carter', 'forenames' => 'Charlie', 'is_staff' => true]);

        $this->actingAs($user);

        // Act - set Joe as assigned_to and Jenny in coseItStaff
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.assignedTo', $joeBlogs->id)
            ->set('schedulingForm.coseItStaff', [$jennySmith->id])
            ->call('toggleHeatmap');

        // Assert
        $heatmapData = $component->get('heatmapData');
        expect($heatmapData['hasAssignedStaff'])->toBeTrue();

        $staffCollection = $heatmapData['staff'];

        // Get the first 2 staff (should be Joe and Jenny in some order, both assigned)
        $topTwoStaffIds = $staffCollection->take(2)->pluck('user.id')->all();

        // Both Joe and Jenny should be in the top 2 positions
        expect($topTwoStaffIds)->toContain($joeBlogs->id)
            ->and($topTwoStaffIds)->toContain($jennySmith->id);

        // Alice/Bob/Charlie (unassigned) should NOT be in top 2, despite alphabetical advantage
        expect($topTwoStaffIds)->not->toContain($alice->id)
            ->and($topTwoStaffIds)->not->toContain($bob->id)
            ->and($topTwoStaffIds)->not->toContain($charlie->id);

        // Verify unassigned staff come after (position 3+)
        $remainingStaffIds = $staffCollection->skip(2)->pluck('user.id')->all();
        expect($remainingStaffIds)->toContain($alice->id)
            ->and($remainingStaffIds)->toContain($bob->id)
            ->and($remainingStaffIds)->toContain($charlie->id);
    });

    it('loads assigned staff from database correctly when reopening project', function () {
        // Arrange - This tests data persistence: save to DB, then reload
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();

        $joeBlogs = User::factory()->create(['surname' => 'Blogs', 'forenames' => 'Joe', 'is_staff' => true]);
        $jennySmith = User::factory()->create(['surname' => 'Smith', 'forenames' => 'Jenny', 'is_staff' => true]);
        $alice = User::factory()->create(['surname' => 'Anderson', 'forenames' => 'Alice', 'is_staff' => true]);
        $bob = User::factory()->create(['surname' => 'Baker', 'forenames' => 'Bob', 'is_staff' => true]);

        $this->actingAs($user);

        // Act - Save the scheduling data to database (must fill all required fields for validation)
        $component = livewire(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.keySkills', 'PHP, Laravel')
            ->set('schedulingForm.estimatedStartDate', now()->addDays(5)->format('Y-m-d'))
            ->set('schedulingForm.estimatedCompletionDate', now()->addDays(15)->format('Y-m-d'))
            ->set('schedulingForm.changeBoardDate', now()->addDays(3)->format('Y-m-d'))
            ->set('schedulingForm.priority', \App\Enums\Priority::PRIORITY_2->value)
            ->set('schedulingForm.assignedTo', $joeBlogs->id)
            ->set('schedulingForm.coseItStaff', [$jennySmith->id])
            ->call('save', 'scheduling');

        // Verify data was actually saved to database
        $project->refresh();
        expect($project->scheduling->assigned_to)->toBe($joeBlogs->id)
            ->and($project->scheduling->cose_it_staff)->toBe([$jennySmith->id]);

        // Act - Now open a FRESH component instance (simulates page reload)
        $freshComponent = livewire(ProjectEditor::class, ['project' => $project->fresh()])
            ->call('toggleHeatmap');

        // Assert - Check that loaded data produces correct heatmap sorting
        $heatmapData = $freshComponent->get('heatmapData');
        expect($heatmapData['hasAssignedStaff'])->toBeTrue();

        $staffCollection = $heatmapData['staff'];
        $topTwoStaffIds = $staffCollection->take(2)->pluck('user.id')->all();

        // Both Joe and Jenny should STILL be in top 2 after reload
        expect($topTwoStaffIds)->toContain($joeBlogs->id)
            ->and($topTwoStaffIds)->toContain($jennySmith->id);

        // Alice and Bob (alphabetically earlier) should come after
        expect($topTwoStaffIds)->not->toContain($alice->id)
            ->and($topTwoStaffIds)->not->toContain($bob->id);
    });

    it('returns correct structure in heatmapData computed property', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();
        User::factory()->count(3)->create(['is_staff' => true]);

        $this->actingAs($user);

        // Act
        $component = livewire(ProjectEditor::class, ['project' => $project]);
        $heatmapData = $component->get('heatmapData');

        // Assert - structure contains all required keys
        expect($heatmapData)->toHaveKeys(['days', 'staff', 'projects', 'component', 'hasAssignedStaff'])
            ->and($heatmapData['days'])->toBeArray()
            ->and($heatmapData['days'])->toHaveCount(10) // 10 working days
            ->and($heatmapData['staff'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($heatmapData['projects'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($heatmapData['component'])->toBeInstanceOf(ProjectEditor::class)
            ->and($heatmapData['hasAssignedStaff'])->toBeBool();
    });

    it('displays UI elements correctly when heatmap is shown', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();
        User::factory()->count(2)->create(['is_staff' => true]);

        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->call('toggleHeatmap')
            ->assertSeeHtml('data-test="model-heatmap-button"')
            ->assertSee('Hide Heatmap')
            ->assertSee('Staff Heatmap')
            ->assertSee('All staff members are shown alphabetically')
            ->assertSeeHtml('data-test="heatmap-grid"');
    });

    it('updates button label when toggling heatmap', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();
        $this->actingAs($user);

        // Act & Assert - button text changes
        livewire(ProjectEditor::class, ['project' => $project])
            ->assertSee('Model')
            ->assertDontSee('Hide Heatmap')
            ->call('toggleHeatmap')
            ->assertSee('Hide Heatmap')
            ->assertDontSee('Model');
    });

    it('shows correct message when staff are assigned', function () {
        // Arrange
        $user = User::factory()->create(['is_admin' => true]);
        $project = $this->createProject();
        $assignedStaff = User::factory()->create(['is_staff' => true]);

        $this->actingAs($user);

        // Act & Assert
        livewire(ProjectEditor::class, ['project' => $project])
            ->set('schedulingForm.assignedTo', $assignedStaff->id)
            ->call('toggleHeatmap')
            ->assertSee('Assigned staff are shown first, followed by all other staff members')
            ->assertDontSee('All staff members are shown alphabetically');
    });
});
