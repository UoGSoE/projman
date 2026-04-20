<?php

use App\Livewire\ProjectEditor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('does not include requesters in the staff picker dropdowns', function () {
    $this->fakeNotifications();

    $admin = User::factory()->admin()->create();
    $itStaff = User::factory()->staff()->create(['surname' => 'Detective']);
    $requester = User::factory()->requester()->create(['surname' => 'Detective']);
    $project = Project::factory()->create();

    $this->actingAs($admin);

    $component = livewire(ProjectEditor::class, ['project' => $project]);

    $availableUserIds = collect($component->instance()->availableUsers)->pluck('id');

    expect($availableUserIds)->toContain($itStaff->id);
    expect($availableUserIds)->not->toContain($requester->id);
});
