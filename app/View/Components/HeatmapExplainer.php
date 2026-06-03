<?php

namespace App\View\Components;

use App\Enums\AvailabilityForChange;
use App\Enums\EffortScale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class HeatmapExplainer extends Component
{
    /** @var Collection<int, array{label: string, days: int}> */
    public Collection $bands;

    /** @var Collection<int, array{label: string, percent: int}> */
    public Collection $availabilityLevels;

    /** @var array<int, string> */
    public array $singleRoles;

    /** @var array<int, string> */
    public array $multiRoles;

    /** @var array{amber: int, red: int, black: int} */
    public array $thresholds;

    public function __construct()
    {
        // Effort bands and availability levels come straight from the real enums.
        // Bands are shown exactly as specced: label() carries a range (e.g.
        // "Medium (6-15 days)") while the calc uses the single estimatedDays()
        // value. The gap and the range-vs-point mismatch are intentional.
        $this->bands = collect(EffortScale::cases())
            ->map(fn (EffortScale $band) => ['label' => $band->label(), 'days' => $band->estimatedDays()])
            ->values();

        $this->availabilityLevels = collect(AvailabilityForChange::cases())
            ->map(fn (AvailabilityForChange $level) => ['label' => $level->name, 'percent' => $level->value])
            ->values();

        // Counted roles, hardcoded to mirror App\Models\Project::teamMemberIds().
        // There is no declarative role list to read there (it returns user IDs
        // imperatively), so if a contributing role is added there, add it here
        // too. Single-person roles render as checkboxes; the two multi-person
        // array fields (cose_it_staff, development_team) render as number inputs.
        $this->singleRoles = [
            'Assigned to',
            'Technical lead',
            'Change champion',
            'Scoping assessor',
            'Feasibility assessor',
            'Detailed designer',
            'Lead developer',
            'Test lead',
        ];

        $this->multiRoles = [
            'CoSE IT staff',
            'Development team',
        ];

        // Mirrors App\Support\HeatmapCell::colour() (the source of truth):
        // utilisation % over 100 black, >=90 red, >=70 amber, else green.
        $this->thresholds = ['amber' => 70, 'red' => 90, 'black' => 100];
    }

    public function render(): View
    {
        return view('components.heatmap-explainer');
    }
}
