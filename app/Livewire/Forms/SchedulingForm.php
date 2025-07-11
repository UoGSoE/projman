<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class SchedulingForm extends Form
{
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
    public string $keySkills = '';

    #[Validate('string|max:1024')]
    public string $coseItStaff = '';

    #[Validate('required|date|after:today')]
    public string $estimatedStartDate = '';

    #[Validate('required|date|after:estimatedStartDate')]
    public string $estimatedCompletionDate = '';

    #[Validate('required|date|after:today')]
    public string $changeBoardDate = '';

    #[Validate('required|string|max:255')]
    public string $priority = '';

    #[Validate('required|integer|exists:users,id')]
    public ?int $assignedTo = null;

    #[Validate('required|string|max:255')]
    public string $teamAssignment = '';

    public function save()
    {
        $this->validate();
        Flux::toast('Scheduling saved', variant: 'success');
    }

    public function saveToDatabase($project)
    {
        // Create or update scheduling record
        $project->scheduling()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'key_skills' => $this->keySkills,
                'cose_it_staff' => $this->coseItStaff,
                'estimated_start_date' => $this->estimatedStartDate,
                'estimated_completion_date' => $this->estimatedCompletionDate,
                'change_board_date' => $this->changeBoardDate,
                'assigned_to' => $this->assignedTo,
                'priority' => $this->priority,
                'team_assignment' => $this->teamAssignment,
            ]
        );

        Flux::toast('Scheduling saved', variant: 'success');
    }
}
