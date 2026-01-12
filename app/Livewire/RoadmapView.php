<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\Carbon;
use Flux\DateRange;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class RoadmapView extends Component
{
    #[Url]
    public ?DateRange $dateRange = null;

    public function mount(): void
    {
        $this->dateRange ??= new DateRange(now()->startOfWeek(), now()->addMonths(3)->endOfWeek());
    }

    public function render()
    {
        $allProjects = $this->projects();
        $timelineStart = $this->dateRange->start()->copy()->startOfWeek();
        $timelineEnd = $this->dateRange->end()->copy()->endOfWeek();
        $totalWeeks = (int) $timelineStart->diffInWeeks($timelineEnd) + 1;

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
        $rangeStart = $this->dateRange->start();
        $rangeEnd = $this->dateRange->end();

        return Project::query()
            ->with([
                'user',
                'scheduling',
            ])
            ->whereNotIn('status', [ProjectStatus::CANCELLED])
            ->whereHas('scheduling', function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereNotNull('estimated_start_date')
                    ->whereNotNull('estimated_completion_date')
                    ->where('estimated_start_date', '<=', $rangeEnd)
                    ->where('estimated_completion_date', '>=', $rangeStart);
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
     * Calculate month spans for headers.
     */
    private function calculateMonthSpans(Carbon $timelineStart, Carbon $timelineEnd): Collection
    {
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
                'label' => $current->format('M Y'),
                'span' => $span,
            ]);

            $current->addMonth();
        }

        return $months;
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
            'black' => 'bg-[rgb(27,122,200)] text-white',
            'red' => 'bg-red-600 dark:bg-red-700 text-white',
            'amber' => 'bg-amber-500 dark:bg-amber-600 text-white',
            'green' => 'bg-green-600 dark:bg-green-700 text-white',
        };
    }
}
