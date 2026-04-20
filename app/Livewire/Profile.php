<?php

namespace App\Livewire;

use App\Enums\Busyness;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Profile extends Component
{
    public ?Busyness $busynessWeek1 = null;

    public ?Busyness $busynessWeek2 = null;

    public function render(): View
    {
        $user = auth()->user()->load(['skills' => fn ($q) => $q->orderBy('name')]);

        return view('livewire.profile', [
            'skills' => $user->skills,
            'busynessOptions' => Busyness::cases(),
        ]);
    }

    public function mount(): void
    {
        $this->loadBusynessData();
    }

    public function loadBusynessData(): void
    {
        $user = auth()->user();
        $this->busynessWeek1 = $user->busyness_week_1 ?? Busyness::LOW;
        $this->busynessWeek2 = $user->busyness_week_2 ?? Busyness::LOW;
    }

    public function updateBusyness(): void
    {
        auth()->user()->update([
            'busyness_week_1' => $this->busynessWeek1,
            'busyness_week_2' => $this->busynessWeek2,
        ]);
    }

    public function updatedBusynessWeek1(): void
    {
        $this->updateBusyness();
    }

    public function updatedBusynessWeek2(): void
    {
        $this->updateBusyness();
    }
}
