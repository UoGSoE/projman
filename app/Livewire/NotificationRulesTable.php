<?php

namespace App\Livewire;

use App\Models\NotificationRule;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationRulesTable extends Component
{
    use WithPagination;

    public $search = '';

    public $status = 'active';

    public $maxDisplayedProjects = 2;

    public $maxDisplayedRoles = 2;

    public $maxDisplayedUsers = 2;

    public $editingRule = null;

    public $editRuleName = '';

    public $editRuleDescription = '';

    public $editRuleEvent = '';

    public $editRuleAppliesToAll = true;

    public $editSelectedProjects = [];

    public $editRecipientTypes = 'roles';

    public $editSelectedRoles = [];

    public $editSelectedUsers = [];

    public $editRuleStatus = false;

    public $editFormModified = false;

    public $deletingRule = null;

    public function render()
    {
        return view('livewire.notification-rules-table', [
            'rules' => $this->getRules(),
            'events' => config('notifiable_events'),
            'roles' => $this->getRoles(),
            'users' => $this->getUsers(),
            'projects' => $this->getProjects(),
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

    public function getRoles(): array
    {
        return Role::pluck('name', 'id')->toArray();
    }

    public function getUsers(): array
    {
        return User::query()
            ->select('id', 'forenames', 'surname')
            ->get()
            ->reduce(function ($acc, $user) {
                $acc[$user->id] = $user->forenames.' '.$user->surname;

                return $acc;
            }, []);
    }

    public function getProjects(): array
    {
        return Project::pluck('title', 'id')->toArray();
    }

    public function openEditNotificationRuleModal($ruleId)
    {
        $this->editingRule = NotificationRule::findOrFail($ruleId);

        $this->editRuleName = $this->editingRule->name;
        $this->editRuleDescription = $this->editingRule->description;
        $this->editRuleEvent = $this->editingRule->event;
        $this->editRuleAppliesToAll = in_array('all', $this->editingRule->applies_to ?? []);
        $this->editSelectedProjects = $this->editRuleAppliesToAll ? [] : ($this->editingRule->applies_to ?? []);
        // dd($this->editSelectedProjects);

        if (isset($this->editingRule->recipients['roles']) && ! empty($this->editingRule->recipients['roles'])) {
            $this->editRecipientTypes = 'roles';
            $this->editSelectedRoles = $this->editingRule->recipients['roles'];
            $this->editSelectedUsers = [];
        } elseif (isset($this->editingRule->recipients['users']) && ! empty($this->editingRule->recipients['users'])) {
            $this->editRecipientTypes = 'users';
            $this->editSelectedUsers = $this->editingRule->recipients['users'];
            $this->editSelectedRoles = [];
        } else {
            $this->editRecipientTypes = 'roles';
            $this->editSelectedRoles = [];
            $this->editSelectedUsers = [];
        }

        $this->editRuleStatus = $this->editingRule->active;
        $this->editFormModified = false;
    }

    public function openDeleteNotificationRuleModal($ruleId)
    {
        $this->deletingRule = NotificationRule::findOrFail($ruleId);
        Flux::modal('delete-notification-rule')->show();
    }

    public function updateRule()
    {
        $this->validate([
            'editRuleName' => 'required|string|max:255',
            'editRuleDescription' => 'required|string|max:255',
            'editRuleEvent' => 'required|string|max:255',
            'editRuleAppliesToAll' => 'required|boolean',
            'editSelectedProjects' => 'array',
            'editRecipientTypes' => 'required|string|max:255',
            'editSelectedRoles' => 'array',
            'editSelectedUsers' => 'array',
            'editRuleStatus' => 'required|boolean',
        ]);

        $recipients = $this->editRecipientTypes === 'users'
            ? ['users' => $this->editSelectedUsers]
            : ['roles' => $this->editSelectedRoles];

        $this->editingRule->update([
            'name' => $this->editRuleName,
            'description' => $this->editRuleDescription,
            'event' => $this->editRuleEvent,
            'applies_to' => $this->editRuleAppliesToAll ? ['all'] : $this->editSelectedProjects,
            'recipients' => $recipients,
            'active' => $this->editRuleStatus,
        ]);

        $this->resetEditForm();
        Flux::modal('edit-notification-rule')->close();
    }

    public function deleteRule()
    {
        if ($this->deletingRule) {
            $this->deletingRule->delete();
            $this->deletingRule = null;
        }

        Flux::modal('delete-notification-rule')->close();
    }

    public function resetEditForm()
    {
        $this->editingRule = null;
        $this->editRuleName = '';
        $this->editRuleDescription = '';
        $this->editRuleEvent = '';
        $this->editRuleAppliesToAll = true;
        $this->editSelectedProjects = [];
        $this->editRecipientTypes = 'roles';
        $this->editSelectedRoles = [];
        $this->editSelectedUsers = [];
        $this->editRuleStatus = false;
        $this->editFormModified = false;
    }

    public function markEditFormAsModified()
    {
        $this->editFormModified = true;
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'edit')) {
            $this->markEditFormAsModified();
        }
    }
}
