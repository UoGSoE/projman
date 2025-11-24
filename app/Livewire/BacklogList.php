<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BacklogList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public function render()
    {
        return view('livewire.backlog-list', [
            'projects' => $this->getProjects(),
            'projectStatuses' => $this->getProjectStatuses(),
        ]);
    }

    private function getProjects()
    {
        return Project::query()
            ->with([
                'user',
                'scoping',
                'scheduling.assignedUser',
                'scheduling.changeChampion',
                'ideation',
            ])
            ->whereNotIn('status', [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when(
                $this->statusFilter !== 'all',
                fn ($q) => $q->where('status', $this->statusFilter)
            )
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    private function getProjectStatuses(): array
    {
        return collect(ProjectStatus::cases())
            ->reject(fn ($status) => in_array($status, [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED]))
            ->all();
    }
}
