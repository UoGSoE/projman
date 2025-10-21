<?php

namespace App\Livewire;

use App\Models\NotificationRule;
use Livewire\Component;

class NotificationRulesTable extends Component
{
    public $search = '';

    public $status = 'active';

    public $maxDisplayedProjects = 5;

    public $maxDisplayedRoles = 5;

    public $maxDisplayedUsers = 5;

    public function render()
    {
        return view('livewire.notification-rules-table', [
            'rules' => $this->getRules(),
        ]);
    }

    public function mount()
    {

        // dd(NotificationRule::all());

    }

    public function getRules()
    {
        $searchTerm = $this->search;

        return NotificationRule::query()->when(
            strlen($searchTerm) > 1,
            fn ($query) => $query->where('name', 'like', '%'.$searchTerm.'%')
                ->orWhere('description', 'like', '%'.$searchTerm.'%')

        )->paginate(10);
    }
}
