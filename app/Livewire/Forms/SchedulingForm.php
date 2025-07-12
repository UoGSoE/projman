<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class SchedulingForm extends Form
{
    public ?Project $project = null;

    public array $availableUsers = [
        '1' => 'Jenny',
        '2' => 'John',
        '3' => 'Sarah',
    ];

    public array $availablePriorities = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public array $availableTeams = [
        '1' => 'Development Team',
        '2' => 'Infrastructure Team',
        '3' => 'Support Team',
    ];

    #[Validate('required|string|max:1024')]
    public ?string $keySkills;

    #[Validate('string|max:1024')]
    public ?string $coseItStaff;

    #[Validate('required|date|after:today')]
    public ?string $estimatedStartDate;

    #[Validate('required|date|after:estimatedStartDate')]
    public ?string $estimatedCompletionDate;

    #[Validate('required|date|after:today')]
    public ?string $changeBoardDate;

    #[Validate('required|string|max:255')]
    public ?string $priority;

    #[Validate('required|integer|exists:users,id')]
    public ?int $assignedTo = null;

    #[Validate('required|string|max:255')]
    public ?string $teamAssignment;

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->keySkills = $project->scheduling->key_skills;
        $this->coseItStaff = $project->scheduling->cose_it_staff;
        $this->estimatedStartDate = $project->scheduling->estimated_start_date;
        $this->estimatedCompletionDate = $project->scheduling->estimated_completion_date;
        $this->changeBoardDate = $project->scheduling->change_board_date;
        $this->priority = $project->scheduling->priority;
        $this->assignedTo = $project->scheduling->assigned_to;
        $this->teamAssignment = $project->scheduling->team_assignment;
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
            'priority' => $this->priority,
            'team_assignment' => $this->teamAssignment,
        ]);
    }
}
