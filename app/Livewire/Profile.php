<?php

namespace App\Livewire;

use App\Enums\AvailabilityForChange;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Profile extends Component
{
    public ?AvailabilityForChange $availabilityForChange = null;

    public function mount(): void
    {
        $this->availabilityForChange = auth()->user()->availability_for_change ?? AvailabilityForChange::Moderate;
    }

    public function render(): View
    {
        $user = auth()->user()->load(['skills' => fn ($q) => $q->orderBy('name')]);

        return view('livewire.profile', [
            'skills' => $user->skills,
            'availabilityOptions' => AvailabilityForChange::cases(),
        ]);
    }

    public function updatedAvailabilityForChange(): void
    {
        auth()->user()->update([
            'availability_for_change' => $this->availabilityForChange,
        ]);
    }
}
