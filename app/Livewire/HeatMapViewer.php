<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\HasHeatmapData;
use Livewire\Attributes\Url;
use Livewire\Component;

class HeatMapViewer extends Component
{
    use HasHeatmapData;

    #[Url]
    public string $viewMode = 'days';

    #[Url]
    public array $nameFilter = [];

    public function render()
    {
        $buckets = $this->getDateBuckets();
        $staff = $this->staffWithBusynessForBuckets($buckets);
        $activeProjects = $this->activeProjects();

        $allStaff = User::query()
            ->where('is_staff', true)
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();

        if (! empty($this->nameFilter)) {
            $staff = $staff->filter(fn ($item) => in_array($item['user']->id, $this->nameFilter));
        }

        return view('livewire.heat-map-viewer', [
            'buckets' => $buckets,
            'staff' => $staff,
            'activeProjects' => $activeProjects,
            'viewMode' => $this->viewMode,
            'allStaff' => $allStaff,
        ]);
    }
}
