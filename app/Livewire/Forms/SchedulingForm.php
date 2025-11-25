<?php

namespace App\Livewire\Forms;

use App\Enums\ChangeBoardOutcome;
use App\Enums\Priority;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SchedulingForm extends Form
{
    public ?Project $project = null;

    public array $availableUsers = [
        '1' => 'Jenny',
        '2' => 'John',
        '3' => 'Sarah',
    ];

    public array $availableTeams = [
        '1' => 'Development Team',
        '2' => 'Infrastructure Team',
        '3' => 'Support Team',
    ];

    #[Validate('required|string|max:1024')]
    public ?string $keySkills;

    #[Validate('array')]
    public ?array $coseItStaff = [];

    #[Validate('required|date|after:today')]
    public ?string $estimatedStartDate;

    #[Validate('required|date|after:estimatedStartDate')]
    public ?string $estimatedCompletionDate;

    #[Validate('required|date|after:today')]
    public ?string $changeBoardDate;

    #[Validate]
    public ?Priority $priority = null;

    #[Validate('required|integer|exists:users,id')]
    public ?int $assignedTo = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $technicalLeadId = null;

    #[Validate('nullable|integer|exists:users,id')]
    public ?int $changeChampionId = null;

    #[Validate]
    public ?ChangeBoardOutcome $changeBoardOutcome = null;

    // #[Validate('required|string|max:255')]
    // public ?string $teamAssignment;

    public ?Carbon $submittedToDcggAt = null;

    public ?int $submittedToDcggBy = null;

    public ?Carbon $scheduledAt = null;

    public function rules(): array
    {
        return [
            'changeBoardOutcome' => ['nullable', Rule::enum(ChangeBoardOutcome::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
        ];
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->keySkills = $project->scheduling->key_skills;
        // dd($project->scheduling->cose_it_staff);
        $this->coseItStaff = $project->scheduling->cose_it_staff ?? [];
        $this->estimatedStartDate = $project->scheduling->estimated_start_date?->format('Y-m-d');
        $this->estimatedCompletionDate = $project->scheduling->estimated_completion_date?->format('Y-m-d');
        $this->changeBoardDate = $project->scheduling->change_board_date?->format('Y-m-d');
        $this->priority = $project->scheduling->priority;
        $this->assignedTo = $project->scheduling->assigned_to;
        $this->technicalLeadId = $project->scheduling->technical_lead_id;
        $this->changeChampionId = $project->scheduling->change_champion_id;
        $this->changeBoardOutcome = $project->scheduling->change_board_outcome;
        $this->submittedToDcggAt = $project->scheduling->submitted_to_dcgg_at;
        $this->submittedToDcggBy = $project->scheduling->submitted_to_dcgg_by;
        $this->scheduledAt = $project->scheduling->scheduled_at;
        // $this->teamAssignment = $project->scheduling->team_assignment;
    }

    public function save()
    {
        $this->project->scheduling->update([
            'key_skills' => $this->keySkills,
            'cose_it_staff' => $this->coseItStaff,
            'estimated_start_date' => $this->estimatedStartDate,
            'estimated_completion_date' => $this->estimatedCompletionDate,
            'change_board_date' => $this->changeBoardDate,
            'assigned_to' => $this->assignedTo,
            'technical_lead_id' => $this->technicalLeadId,
            'change_champion_id' => $this->changeChampionId,
            'change_board_outcome' => $this->changeBoardOutcome?->value,
            'priority' => $this->priority?->value,
            // 'team_assignment' => $this->teamAssignment,
        ]);
    }

    public function submitToDCGG(): void
    {
        $this->validate();

        $this->submittedToDcggAt = now();
        $this->submittedToDcggBy = Auth::id();

        $this->project->scheduling->update([
            'submitted_to_dcgg_at' => $this->submittedToDcggAt,
            'submitted_to_dcgg_by' => $this->submittedToDcggBy,
        ]);

        $this->setProject($this->project->fresh());

        event(new \App\Events\SchedulingSubmittedToDCGG($this->project));
        event(new \App\Events\ProjectUpdated($this->project, 'Submitted scheduling to DCGG for approval'));
    }

    public function schedule(): void
    {
        $this->validate([
            'changeBoardDate' => 'required|date',
        ]);

        $this->scheduledAt = now();

        $this->project->scheduling->update([
            'scheduled_at' => $this->scheduledAt,
        ]);

        $this->setProject($this->project->fresh());

        event(new \App\Events\SchedulingScheduled($this->project));
        event(new \App\Events\ProjectUpdated($this->project, 'Scheduling approved and scheduled'));
    }
}
