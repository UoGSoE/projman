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
     * Determine the busyness enum for the given user/day index.
     *
     * If adjustment is non-zero, calculates projected busyness from actual
     * project count plus adjustment (for live preview of staff changes).
     */
    public function busynessForDay(User $user, int $dayIndex, int $adjustment = 0): Busyness
    {
        // Fast path: no adjustment, return stored value
        if ($adjustment === 0) {
            return match (intdiv($dayIndex, 5)) {
                0 => $user->busyness_week_1 ?? Busyness::UNKNOWN,
                default => $user->busyness_week_2 ?? Busyness::UNKNOWN,
            };
        }

        // With adjustment: calculate from actual project count
        $baseCount = $user->activeAssignedProjectCount();
        $adjustedCount = max(0, $baseCount + $adjustment);

        return Busyness::fromProjectCount($adjustedCount);
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
        $staff = User::query()
            ->where('is_staff', true)
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
