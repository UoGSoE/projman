<?php

namespace App\Livewire\Forms;

use Flux\Flux;
use Livewire\Form;
use Livewire\Attributes\Validate;
use App\Models\Project;

class Development extends Form
{
    public array $availableDevelopers = [
        '1' => 'Alice',
        '2' => 'Bob',
        '3' => 'Charlie',
    ];

    public array $availableStatuses = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'code_review' => 'Code Review',
        'testing' => 'Testing',
        'completed' => 'Completed',
    ];

    #[Validate('required|string|max:255')]
    public string $deliverableTitle = '';

    #[Validate('required|integer|exists:users,id')]
    public ?int $leadDeveloper = null;

    #[Validate('required|string|max:255')]
    public string $developmentTeam = '';

    #[Validate('required|string|max:2048')]
    public string $technicalApproach = '';

    #[Validate('required|string|max:2048')]
    public string $developmentNotes = '';

    #[Validate('required|url|max:255')]
    public string $repositoryLink = '';

    #[Validate('required|string|max:255')]
    public string $status = '';

    #[Validate('required|date')]
    public string $startDate = '';

    #[Validate('required|date|after:startDate')]
    public string $completionDate = '';

    #[Validate('nullable|string|max:2048')]
    public string $codeReviewNotes = '';

    public function save()
    {
        $this->validate();
        Flux::toast('Development saved', variant: 'success');
    }

    public function saveToDatabase($project)
    {
        // Create or update development record
        $project->development()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'deliverable_title' => $this->deliverableTitle,
                'lead_developer' => $this->leadDeveloper,
                'development_team' => $this->developmentTeam,
                'technical_approach' => $this->technicalApproach,
                'development_notes' => $this->developmentNotes,
                'repository_link' => $this->repositoryLink,
                'status' => $this->status,
                'start_date' => $this->startDate,
                'completion_date' => $this->completionDate,
                'code_review_notes' => $this->codeReviewNotes,
            ]
        );

        Flux::toast('Development saved', variant: 'success');
    }
}
