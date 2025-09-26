<?php

namespace App\Livewire;

use App\Enums\Busyness;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Component;

class HeatMapViewer extends Component
{
    public function render()
    {
        $days = $this->upcomingWorkingDays();
        $staff = $this->staffWithBusyness($days);
        $activeProjects = $this->activeProjects();

        return view('livewire.heat-map-viewer', [
            'days' => $days,
            'staff' => $staff,
            'activeProjects' => $activeProjects,
        ]);
    }

    /**
     * Determine the busyness enum for the given user/day index.
     */
    public function busynessForDay(User $user, int $dayIndex): Busyness
    {
        return match (intdiv($dayIndex, 5)) {
            0 => $user->busyness_week_1 ?? Busyness::UNKNOWN,
            default => $user->busyness_week_2 ?? Busyness::UNKNOWN,
        };
    }

    /**
     * Upcoming working days (skipping weekends) starting from today.
     *
     * @return array<int, Carbon>
     */
    private function upcomingWorkingDays(int $count = 10): array
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
     */
    private function staffWithBusyness(array $days)
    {
        $staff = User::query()
            ->where('is_staff', true)
            ->select('id', 'forenames', 'surname', 'busyness_week_1', 'busyness_week_2')
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();

        $dayCount = count($days);

        return $staff->map(function (User $user) use ($dayCount) {
            return [
                'user' => $user,
                'busyness' => $this->busynessSeries($user, $dayCount),
            ];
        });
    }

    /**
     * Busyness enum sequence for the requested number of days.
     *
     * @return array<int, Busyness>
     */
    private function busynessSeries(User $user, int $dayCount): array
    {
        return array_map(fn ($index) => $this->busynessForDay($user, $index), range(0, $dayCount - 1));
    }

    /**
     * Currently active projects ordered by their deadline.
     */
    private function activeProjects()
    {
        return Project::query()
            ->currentlyActive()
            ->select('id', 'user_id', 'title', 'deadline', 'status')
            ->with([
                'user:id,forenames,surname',
                'scheduling:id,project_id,cose_it_staff,assigned_to',
                'development:id,project_id,lead_developer,development_team',
                'testing:id,project_id,test_lead',
                'detailedDesign:id,project_id,designed_by',
                'feasibility:id,project_id,assessed_by',
                'scoping:id,project_id,assessed_by',
            ])
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderBy('title')
            ->get()
            ->map(function (Project $project) {
                $project->setRelation('team_members', $this->collectTeamMembers($project));

                return $project;
            });
    }

    /**
     * Gather unique staff members allocated to a project across stages.
     */
    private function collectTeamMembers(Project $project)
    {
        $ownerId = $project->user_id;

        $userIds = collect([
            $ownerId,
            optional($project->scheduling)->assigned_to,
            optional($project->detailedDesign)->designed_by,
            optional($project->development)->lead_developer,
            optional($project->testing)->test_lead,
            optional($project->feasibility)->assessed_by,
            optional($project->scoping)->assessed_by,
        ])
            ->filter()
            ->merge(optional($project->scheduling)->cose_it_staff ?? [])
            ->merge(optional($project->development)->development_team ?? [])
            ->unique()
            ->values();

        if ($ownerId) {
            $userIds = $userIds
                ->reject(fn ($id) => $id === $ownerId)
                ->prepend($ownerId);
        }

        $userIds = $userIds->take(5);

        if ($userIds->isEmpty()) {
            return collect();
        }

        $users = User::query()
            ->select('id', 'forenames', 'surname')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        return $userIds
            ->map(fn ($id) => $users->get($id))
            ->filter();
    }
}
