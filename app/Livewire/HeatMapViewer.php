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
        $staff = $this->staffMembers();
        $activeProjects = $this->activeProjects();

        return view('livewire.heat-map-viewer', compact('days', 'staff', 'activeProjects'));
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
     * Staff members represented in the heatmap.
     */
    private function staffMembers()
    {
        return User::query()
            ->where('is_staff', true)
            ->select('id', 'forenames', 'surname', 'busyness_week_1', 'busyness_week_2')
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    /**
     * Currently active projects ordered by their deadline.
     */
    private function activeProjects()
    {
        return Project::query()
            ->currentlyActive()
            ->select('id', 'user_id', 'title', 'deadline', 'status')
            ->with(['user:id,forenames,surname'])
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderBy('title')
            ->get();
    }
}
