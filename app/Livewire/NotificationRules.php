<?php

namespace App\Livewire;

use App\Models\NotificationRule;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Validate;
use Livewire\Component;

class NotificationRules extends Component
{
    #[Validate('required|string|max:255')]
    public $ruleName = '';

    #[Validate('required|string|max:255')]
    public $ruleDescription = '';

    public $ruleEvent = 'project.created';

    public $ruleAppliesToAll = true;

    public $recipientTypes = 'roles';

    public $selectedRoles = [];

    public $selectedUsers = [];

    public $ruleStatus = false;

    public $formModified = false;

    public function render()
    {
        return view('livewire.notification-rules', [
            'events' => config('notifiable_events'),
            'roles' => $this->getRoles(),
            'users' => $this->getUsers(),
            'projects' => $this->getProjects(),
        ]);
    }

    public function createRule() {}

    public function getRoles(): array
    {
        return Role::pluck('name', 'id')->toArray();
    }

    public function getUsers(): array
    {
        return User::select('id', 'forenames', 'surname')
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

    public function markFormAsNotModified()
    {
        $this->formModified = false;
    }

    public function markFormAsModified()
    {
        $this->formModified = true;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['ruleName', 'ruleDescription', 'ruleEvent', 'ruleAppliesToAll', 'ruleStatus', 'recipientTypes', 'selectedRoles', 'selectedUsers'])) {
            $this->markFormAsModified();
        }
    }

    public function resetCreateRuleModal()
    {
        $this->ruleName = '';
        $this->ruleDescription = '';
        $this->ruleEvent = '';
        $this->ruleAppliesToAll = true;
        $this->selectedProjects = [];
        $this->recipientTypes = 'roles';
        $this->selectedRoles = [];
        $this->selectedUsers = [];
        $this->ruleStatus = false;
        $this->markFormAsNotModified();
    }

    public function saveRule()
    {
        $this->validate();

        $recipients = $this->recipientTypes === 'users' ? ['users' => $this->selectedUsers] : ['roles' => $this->selectedRoles];

        NotificationRule::create([
            'name' => $this->ruleName,
            'description' => $this->ruleDescription,
            'event' => $this->ruleEvent,
            'applies_to' => $this->ruleAppliesToAll ? ['all'] : $this->selectedProjects,
            'recipients' => $recipients,
            'active' => $this->ruleStatus,
        ]);

        Flux::modal('create-rule-modal')->close();
        Flux::toast('Rule created successfully', variant: 'success');
        $this->resetCreateRuleModal();
        $this->markFormAsNotModified();
    }
}
