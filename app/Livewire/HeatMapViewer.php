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
            ->with(['user:id,forenames,surname'])
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderBy('title')
            ->get();
    }
}
