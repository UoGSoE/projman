<?php

namespace App\Livewire;

use App\Traits\HasHeatmapData;
use Livewire\Component;

class HeatMapViewer extends Component
{
    use HasHeatmapData;

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
}
