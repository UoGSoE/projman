<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DevelopmentForm extends Form
{
    public ?Project $project = null;

    public $developmentTeamSearch = '';

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

    #[Validate('required|integer|exists:users,id')]
    public ?int $leadDeveloper = null;

    #[Validate('required|array|min:1')]
    public ?array $developmentTeam = [];

    #[Validate('required|string|max:2048')]
    public ?string $technicalApproach;

    #[Validate('required|string|max:2048')]
    public ?string $developmentNotes;

    #[Validate('url|max:255')]
    public ?string $repositoryLink;

    #[Validate('required|string|max:255')]
    public ?string $status;

    #[Validate('required|date')]
    public ?string $startDate;

    #[Validate('required|date|after:startDate')]
    public ?string $completionDate;

    #[Validate('nullable|string|max:2048')]
    public ?string $codeReviewNotes;

    public string $newNote = '';

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->leadDeveloper = $project->development->lead_developer;
        $this->developmentTeam = $project->development->development_team;
        $this->technicalApproach = $project->development->technical_approach;
        $this->developmentNotes = $project->development->development_notes;
        $this->repositoryLink = $project->development->repository_link;
        $this->status = $project->development->status;
        $this->startDate = $project->development->start_date?->format('Y-m-d');
        $this->completionDate = $project->development->completion_date?->format('Y-m-d');
        $this->codeReviewNotes = $project->development->code_review_notes;
    }

    public function save()
    {
        $this->project->development->update([
            'lead_developer' => $this->leadDeveloper,
            'development_team' => $this->developmentTeam,
            'technical_approach' => $this->technicalApproach,
            'development_notes' => $this->developmentNotes,
            'repository_link' => $this->repositoryLink,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'completion_date' => $this->completionDate,
            'code_review_notes' => $this->codeReviewNotes,
        ]);
    }

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|string|max:65535',
        ]);

        $this->project->development->notes()->create([
            'user_id' => Auth::id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';

        $this->project->development->load('notes');
    }
}
