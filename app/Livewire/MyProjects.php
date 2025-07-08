<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class MyProjects extends Component
{
    public $projects;

    public function mount()
    {
        $this->projects = User::admin()->first()->projects;
        // $this->projects = auth()->user()->projects;
    }

    public function render()
    {
        return view('livewire.my-projects');
    }
}
