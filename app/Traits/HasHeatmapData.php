<?php

namespace App\Traits;

use App\Enums\Busyness;
use App\Models\Project;
use App\Models\User;
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
     * Staff members with busyness calculated for each bucket.
     */
    protected function staffWithBusynessForBuckets(array $buckets, ?array $assignedUserIds = null, array $busynessAdjustments = []): Collection
    {
        $viewMode = property_exists($this, 'viewMode') ? $this->viewMode : 'days';

        $staff = User::itStaff()
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();

        if ($assignedUserIds !== null) {
            $staff = $this->sortStaffByAssignment($staff, $assignedUserIds);
        }

        // For days view, use the existing week_1/week_2 busyness
        if ($viewMode === 'days') {
            return $staff->map(function (User $user) use ($buckets, $busynessAdjustments) {
                $adjustment = $busynessAdjustments[$user->id] ?? 0;

                return [
                    'user' => $user,
                    'busyness' => $this->busynessSeries($user, count($buckets), $adjustment),
                ];
            });
        }

        // For weeks/months, calculate busyness from project assignments
        $projectsByUser = $this->getProjectAssignmentsByUser();

        return $staff->map(function (User $user) use ($buckets, $projectsByUser, $busynessAdjustments) {
            $userProjects = $projectsByUser->get($user->id, collect());
            $adjustment = $busynessAdjustments[$user->id] ?? 0;

            $busyness = array_map(function ($bucket) use ($userProjects, $adjustment) {
                $count = $this->countProjectsInPeriod($userProjects, $bucket['start'], $bucket['end']);
                $baseBusyness = Busyness::fromProjectCount($count);

                return $adjustment === 0 ? $baseBusyness : $baseBusyness->adjustedBy($adjustment);
            }, $buckets);

            return [
                'user' => $user,
                'busyness' => $busyness,
            ];
        });
    }

    /**
     * Get all active project assignments grouped by user ID.
     */
    protected function getProjectAssignmentsByUser(): Collection
    {
        $projects = Project::query()
            ->currentlyActive()
            ->with([
                'scheduling',
                'detailedDesign',
                'development',
                'testing',
                'feasibility',
                'scoping',
            ])
            ->get();

        $assignments = collect();

        foreach ($projects as $project) {
            $userIds = $this->collectTeamMemberIds($project);

            foreach ($userIds as $userId) {
                if (! $assignments->has($userId)) {
                    $assignments->put($userId, collect());
                }
                $assignments->get($userId)->push($project);
            }
        }

        return $assignments;
    }

    /**
     * Count how many projects overlap with the given period.
     */
    protected function countProjectsInPeriod(Collection $projects, Carbon $start, Carbon $end): int
    {
        return $projects->filter(function (Project $project) use ($start, $end) {
            $projectStart = $project->scheduling?->estimated_start_date;
            $projectEnd = $project->scheduling?->estimated_completion_date;

            // If no dates set, assume the project is ongoing
            if (! $projectStart && ! $projectEnd) {
                return true;
            }

            // Project overlaps if it starts before period ends AND ends after period starts
            $startsBeforePeriodEnds = ! $projectStart || $projectStart->lte($end);
            $endsAfterPeriodStarts = ! $projectEnd || $projectEnd->gte($start);

            return $startsBeforePeriodEnds && $endsAfterPeriodStarts;
        })->count();
    }

    /**
     * Determine the busyness enum for the given user/day index.
     *
     * If adjustment is non-zero, shifts the stored busyness level
     * (for live preview of staff assignment changes).
     */
    public function busynessForDay(User $user, int $dayIndex, int $adjustment = 0): Busyness
    {
        $baseBusyness = match (intdiv($dayIndex, 5)) {
            0 => $user->busyness_week_1 ?? Busyness::UNKNOWN,
            default => $user->busyness_week_2 ?? Busyness::UNKNOWN,
        };

        if ($adjustment === 0) {
            return $baseBusyness;
        }

        return $baseBusyness->adjustedBy($adjustment);
    }

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
     * Staff members represented in the heatmap with per-day busyness.
     *
     * @param  array  $busynessAdjustments  Array of user_id => adjustment for live preview
     */
    protected function staffWithBusyness(array $days, ?array $assignedUserIds = null, array $busynessAdjustments = []): Collection
    {
        $staff = User::itStaff()
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();

        // Apply smart sorting if assigned users are provided
        if ($assignedUserIds !== null) {
            $staff = $this->sortStaffByAssignment($staff, $assignedUserIds);
        }

        $dayCount = count($days);

        return $staff->map(function (User $user) use ($dayCount, $busynessAdjustments) {
            $adjustment = $busynessAdjustments[$user->id] ?? 0;

            return [
                'user' => $user,
                'busyness' => $this->busynessSeries($user, $dayCount, $adjustment),
            ];
        });
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
     * Busyness enum sequence for the requested number of days.
     *
     * @return array<int, Busyness>
     */
    protected function busynessSeries(User $user, int $dayCount, int $adjustment = 0): array
    {
        return array_map(
            fn ($index) => $this->busynessForDay($user, $index, $adjustment),
            range(0, $dayCount - 1)
        );
    }

    /**
     * Currently active projects ordered by their deadline.
     */
    protected function activeProjects(): Collection
    {
        $projects = Project::query()
            ->currentlyActive()
            ->with([
                'user',
                'scheduling',
                'development',
                'testing',
                'detailedDesign',
                'feasibility',
                'scoping',
            ])
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderBy('title')
            ->get();

        $teamMembers = $this->teamMembersForProjects($projects);

        return $projects->map(function (Project $project) use ($teamMembers) {
            $project->setRelation('team_members', $this->collectTeamMembers($project, $teamMembers));
            $project->setAttribute('assigned_user_id', optional($project->scheduling)->assigned_to);

            return $project;
        });
    }

    protected function collectTeamMembers(Project $project, Collection $users): Collection
    {
        return $this->collectTeamMemberIds($project)
            ->map(fn ($id) => $users->get($id))
            ->filter()
            ->values();
    }

    protected function collectTeamMemberIds(Project $project): Collection
    {
        // As there are so many people assigned to a project on the forms - this is a bit of a mess.
        return collect([
            optional($project->scheduling)->assigned_to,
            optional($project->detailedDesign)->designed_by,
            optional($project->development)->lead_developer,
            optional($project->testing)->test_lead,
            optional($project->feasibility)->assessed_by,
            optional($project->scoping)->assessed_by,
        ])
            ->filter()
            ->merge(collect(optional($project->scheduling)->cose_it_staff ?? []))
            ->merge(collect(optional($project->development)->development_team ?? []))
            ->unique()
            ->values();
    }

    protected function teamMembersForProjects(Collection $projects): Collection
    {
        $userIds = $projects
            ->flatMap(fn (Project $project) => $this->collectTeamMemberIds($project))
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
