<?php

namespace App\Livewire;

use App\Traits\HasHeatmapData;
use Livewire\Attributes\Url;
use Livewire\Component;

class HeatMapViewer extends Component
{
    use HasHeatmapData;

    #[Url]
    public string $viewMode = 'days';

    public function render()
    {
        $buckets = $this->getDateBuckets();
        $staff = $this->staffWithBusynessForBuckets($buckets);
        $activeProjects = $this->activeProjects();

        return view('livewire.heat-map-viewer', [
            'buckets' => $buckets,
            'staff' => $staff,
            'activeProjects' => $activeProjects,
            'viewMode' => $this->viewMode,
        ]);
    }
}
