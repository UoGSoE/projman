<?php

namespace App\Traits;

use App\Models\Project;
use App\Models\User;
use App\Support\HeatmapCell;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait HasHeatmapData
{
    /**
     * Generate date buckets based on the current view mode.
     *
     * @return array<int, array{label: string, sublabel: string, start: Carbon, end: Carbon}>
     */
    protected function getDateBuckets(int $count = 10): array
    {
        $viewMode = property_exists($this, 'viewMode') ? $this->viewMode : 'days';

        return match ($viewMode) {
            'weeks' => $this->generateWeekBuckets($count),
            'months' => $this->generateMonthBuckets($count),
            default => $this->generateDayBuckets($count),
        };
    }

    /**
     * Generate day buckets (10 working days).
     */
    protected function generateDayBuckets(int $count): array
    {
        $days = $this->upcomingWorkingDays($count);

        return array_map(fn (Carbon $day) => [
            'label' => $day->format('D'),
            'sublabel' => $day->format('d M'),
            'start' => $day->copy()->startOfDay(),
            'end' => $day->copy()->endOfDay(),
        ], $days);
    }

    /**
     * Generate week buckets (10 weeks starting from current week).
     */
    protected function generateWeekBuckets(int $count): array
    {
        $date = Carbon::today()->startOfWeek();
        $buckets = [];

        for ($i = 0; $i < $count; $i++) {
            $weekStart = $date->copy();
            $weekEnd = $date->copy()->endOfWeek();

            $buckets[] = [
                'label' => 'W'.$weekStart->weekOfYear,
                'sublabel' => $weekStart->format('d M'),
                'start' => $weekStart,
                'end' => $weekEnd,
            ];

            $date->addWeek();
        }

        return $buckets;
    }

    /**
     * Generate month buckets (10 months starting from current month).
     */
    protected function generateMonthBuckets(int $count): array
    {
        $date = Carbon::today()->startOfMonth();
        $buckets = [];

        for ($i = 0; $i < $count; $i++) {
            $monthStart = $date->copy();
            $monthEnd = $date->copy()->endOfMonth();

            $buckets[] = [
                'label' => $monthStart->format('M'),
                'sublabel' => $monthStart->format('Y'),
                'start' => $monthStart,
                'end' => $monthEnd,
            ];

            $date->addMonth();
        }

        return $buckets;
    }

    /**
     * Build a heatmap cell per bucket for each staff member, derived from
     * project allocations via Project::perDayCostForUser and normalised
     * against the user's Availability for Change.
     */
    protected function staffWithCellsForBuckets(array $buckets, ?array $assignedUserIds = null, ?int $excludeProjectId = null): Collection
    {
        $staff = User::itStaff()
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();

        if ($assignedUserIds !== null) {
            $staff = $this->sortStaffByAssignment($staff, $assignedUserIds);
        }

        $projectsByUser = $this->getProjectAssignmentsByUser();

        if ($excludeProjectId !== null) {
            $projectsByUser = $projectsByUser->map(
                fn (Collection $projects) => $projects->reject(fn (Project $p) => $p->id === $excludeProjectId)
            );
        }

        return $staff->map(function (User $user) use ($buckets, $projectsByUser) {
            $userProjects = $projectsByUser->get($user->id, collect());

            $cells = array_map(
                fn ($bucket) => $this->cellFor($user, $userProjects, $bucket),
                $buckets
            );

            return [
                'user' => $user,
                'cells' => $cells,
            ];
        });
    }

    /**
     * Sum the per-day cost of every project active in the given bucket and
     * wrap the result in a HeatmapCell.
     */
    protected function cellFor(User $user, Collection $userProjects, array $bucket): HeatmapCell
    {
        $totalCost = $userProjects
            ->filter(fn (Project $project) => $this->projectOverlaps($project, $bucket['start'], $bucket['end']))
            ->sum(fn (Project $project) => $project->perDayCostForUser($user));

        return new HeatmapCell((float) $totalCost);
    }

    /**
     * Whether the given project's scheduled dates overlap the given period.
     */
    protected function projectOverlaps(Project $project, Carbon $start, Carbon $end): bool
    {
        $projectStart = $project->scheduling?->estimated_start_date;
        $projectEnd = $project->scheduling?->estimated_completion_date;

        if (! $projectStart && ! $projectEnd) {
            return true;
        }

        $startsBeforePeriodEnds = ! $projectStart || $projectStart->lte($end);
        $endsAfterPeriodStarts = ! $projectEnd || $projectEnd->gte($start);

        return $startsBeforePeriodEnds && $endsAfterPeriodStarts;
    }

    /**
     * Get all active project assignments grouped by user ID.
     */
    protected function getProjectAssignmentsByUser(): Collection
    {
        $assignments = collect();

        foreach ($this->loadActiveProjects() as $project) {
            foreach ($project->teamMemberIds() as $userId) {
                if (! $assignments->has($userId)) {
                    $assignments->put($userId, collect());
                }
                $assignments->get($userId)->push($project);
            }
        }

        return $assignments;
    }

    /**
     * Active projects with every relation the heatmap needs, loaded once
     * per request and reused by both the per-user assignment lookup and
     * the active-projects list.
     */
    protected function loadActiveProjects(): Collection
    {
        if (isset($this->cachedActiveProjects)) {
            return $this->cachedActiveProjects;
        }

        return $this->cachedActiveProjects = Project::query()
            ->currentlyActive()
            ->with([
                'user',
                'scheduling',
                'detailedDesign',
                'development',
                'testing',
                'feasibility',
                'scoping',
            ])
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderBy('title')
            ->get();
    }

    private ?Collection $cachedActiveProjects = null;

    /**
     * Upcoming working days (skipping weekends) starting from today.
     *
     * @return array<int, Carbon>
     */
    protected function upcomingWorkingDays(int $count = 10): array
    {
        $date = Carbon::today();

        if ($date->isWeekend()) {
            $date = $date->next(Carbon::MONDAY);
        }

        $days = [];

        while (count($days) < $count) {
            if ($date->isWeekday()) {
                $days[] = $date->copy();
            }

            $date->addDay();
        }

        return $days;
    }

    /**
     * Sort staff with assigned users first, then remaining staff alphabetically.
     */
    protected function sortStaffByAssignment(Collection $staff, array $assignedUserIds): Collection
    {
        // Remove duplicates and filter out null values
        $assignedUserIds = collect($assignedUserIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($assignedUserIds)) {
            return $staff;
        }

        // Partition staff into assigned and unassigned
        [$assigned, $unassigned] = $staff->partition(fn (User $user) => in_array($user->id, $assignedUserIds));

        // Sort both groups alphabetically
        $assigned = $assigned->sortBy([
            ['surname', 'asc'],
            ['forenames', 'asc'],
        ])->values();

        $unassigned = $unassigned->sortBy([
            ['surname', 'asc'],
            ['forenames', 'asc'],
        ])->values();

        // Merge: assigned first, then unassigned
        return $assigned->concat($unassigned);
    }

    /**
     * Currently active projects ordered by their deadline, with team_members
     * pre-attached for the project list rendering.
     */
    protected function activeProjects(): Collection
    {
        $projects = $this->loadActiveProjects();
        $teamMembers = $this->teamMembersForProjects($projects);

        return $projects->map(function (Project $project) use ($teamMembers) {
            $project->setRelation('team_members', $this->collectTeamMembers($project, $teamMembers));
            $project->setAttribute('assigned_user_id', optional($project->scheduling)->assigned_to);

            return $project;
        });
    }

    protected function collectTeamMembers(Project $project, Collection $users): Collection
    {
        return $project->teamMemberIds()
            ->map(fn ($id) => $users->get($id))
            ->filter()
            ->values();
    }

    protected function teamMembersForProjects(Collection $projects): Collection
    {
        $userIds = $projects
            ->flatMap(fn (Project $project) => $project->teamMemberIds())
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');
    }
}
