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
        $allProjects = $this->projects();
        $timelineStart = $this->calculateTimelineStart($allProjects);
        $timelineEnd = $this->calculateTimelineEnd($allProjects);
        $totalWeeks = $timelineStart && $timelineEnd
            ? (int) $timelineStart->diffInWeeks($timelineEnd) + 1
            : 0;

        return view('livewire.roadmap-view', [
            'roadmapData' => $this->prepareRoadmapData($allProjects, $timelineStart, $totalWeeks),
            'monthSpans' => $this->calculateMonthSpans($timelineStart, $timelineEnd),
            'totalWeeks' => $totalWeeks,
            'unscheduledProjects' => $this->unscheduledProjects(),
        ]);
    }

    /**
     * Prepare roadmap data with bin-packed lanes.
     */
    private function prepareRoadmapData(Collection $allProjects, ?Carbon $timelineStart, int $totalWeeks): array
    {
        if (! $timelineStart || $totalWeeks === 0) {
            return [];
        }

        $projectsByFunction = $allProjects
            ->groupBy(fn (Project $project) => $project->user->service_function?->label() ?? 'Unassigned')
            ->sortKeys();

        $data = [];

        foreach ($projectsByFunction as $serviceFunction => $projects) {
            // Convert projects to week-slot arrays and pack into lanes
            $projectSlots = $this->projectsToWeekSlots($projects, $timelineStart, $totalWeeks);
            $lanes = $this->packIntoLanes($projectSlots, $totalWeeks);

            $data[] = [
                'serviceFunction' => $serviceFunction,
                'projectCount' => $projects->count(),
                'lanes' => $lanes,
            ];
        }

        return $data;
    }

    /**
     * Convert projects to week-slot data structures.
     * Each project gets: startWeek, endWeek, span, and metadata for display.
     */
    private function projectsToWeekSlots(Collection $projects, Carbon $timelineStart, int $totalWeeks): array
    {
        $slots = [];

        // Sort by start date for better packing
        $sorted = $projects->sortBy(fn ($p) => $p->scheduling->estimated_start_date);

        foreach ($sorted as $project) {
            $startDate = $project->scheduling->estimated_start_date;
            $endDate = $project->scheduling->estimated_completion_date;

            $startWeek = max(0, (int) $timelineStart->diffInWeeks($startDate));
            $endWeek = min($totalWeeks - 1, max(0, (int) $timelineStart->diffInWeeks($endDate)));
            $span = max(1, $endWeek - $startWeek + 1);

            $bragStatus = $this->calculateBRAG($project);

            $slots[] = [
                'project' => $project,
                'startWeek' => $startWeek,
                'endWeek' => $endWeek,
                'span' => $span,
                'colorClasses' => $this->bragColorClasses($bragStatus),
                // Week occupancy array for collision detection
                'weeks' => range($startWeek, $endWeek),
            ];
        }

        return $slots;
    }

    /**
     * Pack project slots into lanes using greedy bin-packing.
     * Each lane contains non-overlapping projects.
     */
    private function packIntoLanes(array $projectSlots, int $totalWeeks): array
    {
        $lanes = [];
        $remaining = $projectSlots;

        while (! empty($remaining)) {
            // Start a new lane with an empty week occupancy array
            $lane = [];
            $occupied = array_fill(0, $totalWeeks, false);
            $stillRemaining = [];

            foreach ($remaining as $slot) {
                // Check if this project fits in the current lane
                $fits = true;
                foreach ($slot['weeks'] as $week) {
                    if ($occupied[$week]) {
                        $fits = false;
                        break;
                    }
                }

                if ($fits) {
                    // Add to this lane and mark weeks as occupied
                    $lane[] = $slot;
                    foreach ($slot['weeks'] as $week) {
                        $occupied[$week] = true;
                    }
                } else {
                    // Doesn't fit, try in next lane
                    $stillRemaining[] = $slot;
                }
            }

            $lanes[] = $lane;
            $remaining = $stillRemaining;
        }

        return $lanes;
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
     * Calculate timeline start (1 week before earliest project).
     */
    private function calculateTimelineStart(Collection $projects): ?Carbon
    {
        $dates = $projects->pluck('scheduling.estimated_start_date')->filter();

        return $dates->isEmpty() ? null : $dates->min()->copy()->startOfWeek();
    }

    /**
     * Calculate timeline end (1 week after latest project).
     */
    private function calculateTimelineEnd(Collection $projects): ?Carbon
    {
        $dates = $projects->pluck('scheduling.estimated_completion_date')->filter();

        return $dates->isEmpty() ? null : $dates->max()->copy()->endOfWeek()->addWeek();
    }

    /**
     * Calculate month spans for headers.
     */
    private function calculateMonthSpans(?Carbon $timelineStart, ?Carbon $timelineEnd): Collection
    {
        if (! $timelineStart || ! $timelineEnd) {
            return collect();
        }

        $months = collect();
        $current = $timelineStart->copy()->startOfMonth();

        while ($current->lte($timelineEnd)) {
            $monthStart = $current->copy();
            $monthEnd = $current->copy()->endOfMonth();

            // Calculate weeks this month spans in our timeline
            $firstWeek = max(0, (int) $timelineStart->diffInWeeks($monthStart->max($timelineStart)));
            $lastWeek = (int) $timelineStart->diffInWeeks($monthEnd->min($timelineEnd));
            $span = max(1, $lastWeek - $firstWeek + 1);

            $months->push([
                'label' => $current->format('M'),
                'span' => $span,
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

    public function calculateBRAG(Project $project): string
    {
        if ($project->status === ProjectStatus::COMPLETED) {
            return 'black';
        }

        $completionDate = $project->scheduling?->estimated_completion_date;

        if (! $completionDate) {
            return 'green';
        }

        if ($completionDate->isPast()) {
            return 'red';
        }

        if (abs($completionDate->diffInDays(now())) < 14) {
            return 'amber';
        }

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
