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

    #[Validate('required|string|max:255')]
    public $ruleEvent = '';

    #[Validate('required|boolean')]
    public $ruleAppliesToAll = true;

    #[Validate('required|array')]
    public $selectedProjects = [];

    #[Validate('required|string|max:255')]
    public $ruleRecipients = '';

    #[Validate('required|string|max:255')]
    public $recipientTypes = 'roles';

    public $selectedRoles = [];

    public $selectedUsers = [];

    #[Validate('required|boolean')]
    public $ruleStatus = false;

    public $formModified = false;

    public function render()
    {
        return view('livewire.notification-rules', [
            'events' => config('notifiable_events'),
            'roles' => Role::all()->pluck('name', 'id'),
            'users' => User::all()->reduce(function ($acc, $user) {
                $acc[$user->id] = $user->forenames.' '.$user->surname;

                return $acc;
            }, []),
            'projects' => Project::all()->pluck('title', 'id'),
        ]);
    }

    public function createRule() {}

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
        if (in_array($propertyName, ['ruleName', 'ruleDescription', 'ruleEvent', 'ruleAppliesTo', 'ruleRecipients', 'ruleStatus', 'recipientTypes', 'selectedRoles', 'selectedUsers'])) {
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
        $this->ruleRecipients = '';
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
        $this->resetCreateRuleModal();
        Flux::modal('create-rule-modal')->close();
    }
}
