<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Ideation;
use App\Models\Project;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Ohffs\SimpleSpout\ExcelSheet;

class ProjectStatusTable extends Component
{
    use WithPagination;

    public $sortBy = 'updated_at';

    public $sortDirection = 'desc';

    #[Locked]
    public ?int $userId = null;

    public $projectStatuses = [];

    public $projectStatus = null;

    #[Url]
    public $schoolGroup = null;

    #[Url]
    public $search = '';

    #[Url]
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
            'schoolGroups' => $this->schoolGroups(),
        ]);
    }

    public function getProjects()
    {
        return $this->filteredProjectsQuery()->paginate(20);
    }

    public function filteredProjectsQuery()
    {
        return Project::query()
            ->with(['user', 'ideation', 'feasibility', 'scoping', 'scheduling', 'detailedDesign', 'development', 'testing', 'deployed'])
            ->when($this->userId, fn ($query) => $query->where('user_id', $this->userId))
            ->when($this->search, fn ($query) => $query->where(
                fn ($matches) => $matches
                    ->where('title', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', fn ($user) => $this->applyNameSearch($user))
            ))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->schoolGroup, fn ($query) => $query->whereHas(
                'ideation',
                fn ($ideation) => $ideation->where('school_group', $this->schoolGroup)
            ))
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    /**
     * A user matches when every word of the search appears in their
     * forenames or surname, so a full "forename surname" search finds them.
     */
    protected function applyNameSearch($query)
    {
        foreach (explode(' ', trim($this->search)) as $word) {
            $query->where(fn ($name) => $name
                ->where('forenames', 'like', '%'.$word.'%')
                ->orWhere('surname', 'like', '%'.$word.'%'));
        }

        return $query;
    }

    public function isFiltered(): bool
    {
        return filled($this->search) || filled($this->schoolGroup) || filled($this->status);
    }

    public function schoolGroups()
    {
        return Ideation::query()
            ->whereNotNull('school_group')
            ->orderBy('school_group')
            ->distinct()
            ->pluck('school_group');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSchoolGroup(): void
    {
        $this->resetPage();
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

    public function export()
    {
        $rows = $this->filteredProjectsQuery()->get()->map(fn ($project) => [
            $project->status->label(),
            $project->title,
            $project->user->full_name,
            $project->updated_at->format('d/m/Y H:i'),
        ]);

        $filename = (new ExcelSheet)->generate(
            $rows->prepend(['Status', 'Title', 'Requested By', 'Last Updated'])->toArray()
        );

        return response()->download($filename, 'work-packages.xlsx')->deleteFileAfterSend();
    }

    public function cancelProject(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->authorize('cancel', $project);
        $project->cancel();
        Flux::toast('Work package cancelled', variant: 'success');
    }
}
