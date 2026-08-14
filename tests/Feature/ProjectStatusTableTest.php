<?php

use App\Enums\ProjectStatus;
use App\Livewire\ProjectStatusTable;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Ohffs\SimpleSpout\ExcelSheet;

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

describe('ProjectStatusTable filters', function () {
    it('filters the list by a title search', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Project::factory()->create(['title' => 'Telescope Archive Migration']);
        Project::factory()->create(['title' => 'Canteen Menu Board']);

        livewire(ProjectStatusTable::class)
            ->set('search', 'Telescope')
            ->assertSee('Telescope Archive Migration')
            ->assertDontSee('Canteen Menu Board');
    });

    it('filters the list by school/group and offers the real groups as options', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $physicsProject = Project::factory()->create(['title' => 'Telescope Archive Migration']);
        $physicsProject->ideation->update(['school_group' => 'Physics & Astronomy']);
        $cateringProject = Project::factory()->create(['title' => 'Canteen Menu Board']);
        $cateringProject->ideation->update(['school_group' => 'Catering Services']);

        livewire(ProjectStatusTable::class)
            ->assertSee('Physics & Astronomy')
            ->assertSee('Catering Services')
            ->set('schoolGroup', 'Physics & Astronomy')
            ->assertSee('Telescope Archive Migration')
            ->assertDontSee('Canteen Menu Board');
    });

    it('filters the list by the requester name', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $requester = User::factory()->create(['forenames' => 'Marisol', 'surname' => 'Quintana']);
        Project::factory()->create(['title' => 'Telescope Archive Migration', 'user_id' => $requester->id]);
        Project::factory()->create(['title' => 'Canteen Menu Board']);

        livewire(ProjectStatusTable::class)
            ->set('search', 'Quintana')
            ->assertSee('Telescope Archive Migration')
            ->assertDontSee('Canteen Menu Board')
            ->set('search', 'Marisol Quintana')
            ->assertSee('Telescope Archive Migration')
            ->assertDontSee('Canteen Menu Board');
    });

    it('keeps the filter controls visible when a filter matches nothing', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Project::factory()->create(['title' => 'Telescope Archive Migration']);

        livewire(ProjectStatusTable::class)
            ->set('search', 'zzz-no-match')
            ->assertDontSee('Telescope Archive Migration')
            ->assertSee('No matching work packages');
    });

    it('filters the list by status', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Project::factory()->create(['title' => 'Telescope Archive Migration', 'status' => ProjectStatus::TESTING]);
        Project::factory()->create(['title' => 'Canteen Menu Board', 'status' => ProjectStatus::IDEATION]);

        livewire(ProjectStatusTable::class)
            ->set('status', ProjectStatus::TESTING->value)
            ->assertSee('Telescope Archive Migration')
            ->assertDontSee('Canteen Menu Board');
    });
});

describe('ProjectStatusTable export', function () {
    it('downloads the filtered list as a spreadsheet', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $telescopeProject = Project::factory()->create([
            'title' => 'Telescope Archive Migration',
            'status' => ProjectStatus::TESTING,
        ]);
        Project::factory()->create(['title' => 'Canteen Menu Board']);

        $component = livewire(ProjectStatusTable::class)
            ->set('search', 'Telescope')
            ->call('export')
            ->assertFileDownloaded('work-packages.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'export-test').'.xlsx';
        file_put_contents($path, base64_decode(data_get($component->effects, 'download.content')));
        $rows = (new ExcelSheet)->import($path);
        unlink($path);

        expect($rows)->toHaveCount(2)
            ->and($rows[0])->toBe(['Status', 'Title', 'Requested By', 'Last Updated'])
            ->and($rows[1])->toBe([
                'Testing',
                'Telescope Archive Migration',
                $telescopeProject->user->full_name,
                $telescopeProject->updated_at->format('d/m/Y H:i'),
            ]);
    });

    it('does not offer the export button on a personal work package list', function () {
        $requester = User::factory()->requester()->create();
        $this->actingAs($requester);

        Project::factory()->create(['user_id' => $requester->id]);

        livewire(ProjectStatusTable::class, ['userId' => $requester->id])
            ->assertDontSeeHtml('data-test="export-work-packages-button"');
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
