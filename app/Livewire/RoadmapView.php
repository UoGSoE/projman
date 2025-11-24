<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RoadmapView extends Component
{
    public function render()
    {
        return view('livewire.roadmap-view', [
            'roadmapData' => $this->prepareRoadmapData(),
            'monthColumns' => $this->monthColumns(),
            'unscheduledProjects' => $this->unscheduledProjects(),
        ]);
    }

    /**
     * Prepare all data needed for the roadmap view.
     * Keeps the view clean and simple.
     */
    private function prepareRoadmapData(): array
    {
        // Cache projects() once to prevent duplicate queries across multiple computed properties
        $allProjects = $this->projects();

        // Calculate timeline bounds from cached projects
        $timelineStart = $this->calculateTimelineStart($allProjects);
        $timelineEnd = $this->calculateTimelineEnd($allProjects);
        $totalMonths = $this->calculateMonthColumns($timelineStart, $timelineEnd)->count();

        // Group projects by service function
        $projectsByFunction = $allProjects
            ->groupBy(fn (Project $project) => $project->user->service_function?->label() ?? 'Unassigned')
            ->sortKeys();

        $data = [];

        foreach ($projectsByFunction as $serviceFunction => $projects) {
            $verticalPositions = $this->calculateVerticalPositions($projects, $timelineStart);
            $maxLane = collect($verticalPositions)->max('lane') ?? 0;
            $rowHeight = ($maxLane + 1) * 50 + 16;

            $projectData = [];
            foreach ($projects as $project) {
                $position = $this->calculateProjectPosition($project, $timelineStart);
                $verticalPos = $verticalPositions[$project->id];
                $bragStatus = $this->calculateBRAG($project);

                // Add 1% left buffer to prevent visual overlap with sticky column
                $leftPercent = (($position['start'] - 2) / $totalMonths) * 100 + 0.5;
                $widthPercent = (($position['end'] - $position['start']) / $totalMonths) * 100 - 0.5;

                $projectData[] = [
                    'project' => $project,
                    'top' => $verticalPos['top'],
                    'left' => $leftPercent,
                    'width' => $widthPercent,
                    'colorClasses' => $this->bragColorClasses($bragStatus),
                ];
            }

            $data[] = [
                'serviceFunction' => $serviceFunction,
                'projectCount' => $projects->count(),
                'rowHeight' => $rowHeight,
                'projects' => $projectData,
            ];
        }

        return $data;
    }

    #[Computed]
    public function projects(): Collection
    {
        return Project::query()
            ->with([
                'user',
                'scheduling',
            ])
            ->whereNotIn('status', [ProjectStatus::CANCELLED])
            ->whereHas('scheduling', function ($query) {
                $query->whereNotNull('estimated_start_date')
                    ->whereNotNull('estimated_completion_date');
            })
            ->get();
    }

    #[Computed]
    public function unscheduledProjects(): Collection
    {
        return Project::query()
            ->with(['user', 'scheduling'])
            ->whereNotIn('status', [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED])
            ->whereDoesntHave('scheduling', function ($query) {
                $query->whereNotNull('estimated_start_date')
                    ->whereNotNull('estimated_completion_date');
            })
            ->get();
    }

    /**
     * Calculate timeline start from a projects collection (avoids duplicate queries).
     */
    private function calculateTimelineStart(Collection $projects): ?Carbon
    {
        $dates = $projects
            ->pluck('scheduling.estimated_start_date')
            ->filter();

        return $dates->isEmpty() ? null : $dates->min()->startOfMonth();
    }

    /**
     * Calculate timeline end from a projects collection (avoids duplicate queries).
     */
    private function calculateTimelineEnd(Collection $projects): ?Carbon
    {
        $dates = $projects
            ->pluck('scheduling.estimated_completion_date')
            ->filter();

        return $dates->isEmpty() ? null : $dates->max()->endOfMonth();
    }

    /**
     * Calculate month columns from timeline bounds (avoids duplicate queries).
     */
    private function calculateMonthColumns(?Carbon $timelineStart, ?Carbon $timelineEnd): Collection
    {
        if (! $timelineStart || ! $timelineEnd) {
            return collect();
        }

        $months = collect();
        $current = $timelineStart->copy();

        while ($current->lte($timelineEnd)) {
            $months->push([
                'date' => $current->copy(),
                'label' => $current->format('M Y'),
            ]);
            $current->addMonth();
        }

        return $months;
    }

    #[Computed]
    public function timelineStart(): ?Carbon
    {
        return $this->calculateTimelineStart($this->projects());
    }

    #[Computed]
    public function timelineEnd(): ?Carbon
    {
        return $this->calculateTimelineEnd($this->projects());
    }

    #[Computed]
    public function monthColumns(): Collection
    {
        return $this->calculateMonthColumns($this->timelineStart(), $this->timelineEnd());
    }

    public function calculateProjectPosition(Project $project, ?Carbon $timelineStart = null): array
    {
        $timelineStart = $timelineStart ?? $this->timelineStart();

        if (! $timelineStart) {
            return ['start' => 2, 'end' => 3];
        }

        $startDate = $project->scheduling->estimated_start_date;
        $endDate = $project->scheduling->estimated_completion_date;

        // Calculate month offset from timeline start
        // Carbon v3: diffInMonths() returns signed values - use abs() for correct positioning
        $startCol = abs($timelineStart->diffInMonths($startDate)) + 2; // +2 for label column
        $endCol = abs($timelineStart->diffInMonths($endDate)) + 3; // +3 because grid is exclusive

        return [
            'start' => $startCol,
            'end' => $endCol,
        ];
    }

    /**
     * Calculate vertical positions for projects to prevent overlap.
     * Think of it like parking cars - find the first available lane.
     */
    public function calculateVerticalPositions(Collection $projects, ?Carbon $timelineStart = null): array
    {
        $positions = [];
        $lanes = []; // Track occupied time ranges per vertical lane

        foreach ($projects as $project) {
            $startDate = $project->scheduling->estimated_start_date;
            $endDate = $project->scheduling->estimated_completion_date;

            // Find first available lane (no time overlap)
            $laneIndex = 0;
            while (isset($lanes[$laneIndex]) && $this->hasTimeOverlap($startDate, $endDate, $lanes[$laneIndex])) {
                $laneIndex++;
            }

            // Reserve this time slot in the lane
            if (! isset($lanes[$laneIndex])) {
                $lanes[$laneIndex] = [];
            }
            $lanes[$laneIndex][] = ['start' => $startDate, 'end' => $endDate];

            // Store position for this project
            $positions[$project->id] = [
                'lane' => $laneIndex,
                'top' => ($laneIndex * 50) + 8, // 50px per lane, 8px initial offset
            ];
        }

        return $positions;
    }

    /**
     * Check if a date range overlaps with any existing ranges in a lane.
     */
    private function hasTimeOverlap(Carbon $start, Carbon $end, array $occupiedRanges): bool
    {
        foreach ($occupiedRanges as $range) {
            // Overlap if: new start is before existing end AND new end is after existing start
            if ($start->lte($range['end']) && $end->gte($range['start'])) {
                return true;
            }
        }

        return false;
    }

    public function calculateBRAG(Project $project): string
    {
        // Black = Completed
        if ($project->status === ProjectStatus::COMPLETED) {
            return 'black';
        }

        $completionDate = $project->scheduling?->estimated_completion_date;

        // No date = assume on track (early planning)
        if (! $completionDate) {
            return 'green';
        }

        // Red = Overdue
        if ($completionDate->isPast()) {
            return 'red';
        }

        // Amber = At risk (within 14 days of deadline)
        if (abs($completionDate->diffInDays(now())) < 14) {
            return 'amber';
        }

        // Green = On track
        return 'green';
    }

    public function bragColorClasses(string $bragStatus): string
    {
        return match ($bragStatus) {
            'black' => 'bg-zinc-900 dark:bg-zinc-950 text-white',
            'red' => 'bg-red-600 dark:bg-red-700 text-white',
            'amber' => 'bg-amber-500 dark:bg-amber-600 text-white',
            'green' => 'bg-green-600 dark:bg-green-700 text-white',
        };
    }
}
