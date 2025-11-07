<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;

class IdeationForm extends Form
{
    public ?Project $project = null;

    public array $availableStrategicInitiatives = [
        "Inspire" => "We will create self-sustaining peer support networks and communities of practice to further grow our innovation community, including an “Innovation 101” programme, the Women Researchers Enterprise Network (WREN), the RISE Founders Club, Investor Days, and the University’s KE & Innovation Awards to recognise our top innovators.",
        "Create" => "We will pump-prime our pipeline via targeted strategic funding sources, including the MedTech Innovation Fund, the Creative Launch Fund, the Social Innovation Fund, and a range of College-specific initiatives, including innovation audits.",
        "Thrive" => "We will support our developing ventures and de-risk our innovations via structured accelerator style support, including the UofG Founders Fund, ICURe, the Infinity G Venture Builder programme (open to externals) and beLAB1407.",
        "Invest" => "We will sustain our spinouts to the next stage of their commercialisation journey through targeted strategic investment in companies directly and growth in the operational capabilities of our holdings company, GUHL.",
    ];

    #[Validate('required|string|max:255')]
    public ?string $schoolGroup;

    #[Validate('required|string|max:255')]
    public ?string $objective;

    #[Validate('required|string|max:2048')]
    public ?string $businessCase;

    #[Validate('required|string|max:2048')]
    public ?string $benefits;

    #[Validate('required|date|after:today')]
    public ?string $deadline;

    #[Validate('required|string')]
    public ?string $initiative;

    public function setProject(Project $project)
    {
        $this->project = $project;

        $this->schoolGroup = $project->ideation?->school_group ?? '';
        $this->objective = $project->ideation?->objective ?? '';
        $this->businessCase = $project->ideation?->business_case ?? '';
        $this->benefits = $project->ideation?->benefits ?? '';
        $this->deadline = $project->ideation?->deadline ? (string) $project->ideation->deadline->format('Y-m-d') : '';
        $this->initiative = $project->ideation?->strategic_initiative ?? '';
    }

    public function save()
    {
        $this->project->ideation->update([
            'school_group' => $this->schoolGroup,
            'objective' => $this->objective,
            'business_case' => $this->businessCase,
            'benefits' => $this->benefits,
            'deadline' => $this->deadline,
            'strategic_initiative' => $this->initiative,
        ]);
    }
}
