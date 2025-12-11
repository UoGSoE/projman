<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectStatusTable extends Component
{
    use WithPagination;

    public $sortBy = 'updated_at';

    public $sortDirection = 'desc';

    public ?int $userId = null;

    public $projectStatuses = [];

    public $projectStatus = null;

    public $schoolGroup = null;

    public $search = '';

    public $status = null;

    public function mount(?int $userId = null)
    {
        $this->userId = $userId;
        $this->projectStatuses = ProjectStatus::cases();
        $this->projectStatus = ProjectStatus::IDEATION;
    }

    public function render()
    {
        return view('livewire.project-status-table', [
            'projects' => $this->getProjects(),
        ]);
    }

    public function getProjects()
    {
        return Project::query()
            ->with(['user', 'ideation', 'feasibility', 'scoping', 'scheduling', 'detailedDesign', 'development', 'testing', 'deployed'])
            ->when($this->userId, fn ($query) => $query->where('user_id', $this->userId))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);
    }

    public function sort($column)
    {
        // Map frontend column names to database columns
        $columnMap = [
            'user' => 'user_id',
            // Add other mappings as needed
        ];

        $dbColumn = $columnMap[$column] ?? $column;

        if ($this->sortBy === $dbColumn) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $dbColumn;
            $this->sortDirection = 'asc';
        }
    }

    public function cancelProject(int $projectId)
    {
        $project = Project::findOrFail($projectId);
        $project->cancel();
        Flux::toast('Work package cancelled', variant: 'success');
    }
}
