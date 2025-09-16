<?php

namespace App\Livewire;

use Livewire\Component;
use App\Livewire\Forms\IdeationForm;
use App\Livewire\Forms\FeasibilityForm;
use App\Models\User;

class HomePage extends Component
{
    public IdeationForm $ideationForm;
    public FeasibilityForm $feasibilityForm;

    public $tab = 'ideation';
    public ?int $formId = null;

    public $skills = [
        'one' => 'Skill',
    ];

    public $users = [
        '1' => 'Jenny',
    ];

    public function render()
    {
        // auth()->loginUsingId(User::admin()->first()->id); //automatically logging in as an admin user every time the HomePage component renders (maybe kept for debugging purposes)
        return view('livewire.home-page', [
            'partOfDay' => $this->partOfDay(),
        ]);
    }

    public function save(string $tabName)
    {
        $this->{$tabName . 'Form'}->save();
    }

    public function partOfDay()
    {
        $hour = now()->hour;
        return match (true) {
            $hour < 12 => 'morning',
            $hour < 18 => 'afternoon',
            default => 'evening',
        };
    }
}
