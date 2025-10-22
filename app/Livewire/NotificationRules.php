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

    public $selectedProjectStage = 'ideation';

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
            'projectStages' => $this->getProjectStages(),
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

    public function getProjectStages(): array
    {
        return [
            'ideation' => 'Ideation',
            'feasibility' => 'Feasibility',
            'scoping' => 'Scoping',
            'scheduling' => 'Scheduling',
            'detailed-design' => 'Detailed Design',
            'development' => 'Development',
            'testing' => 'Testing',
            'deployed' => 'Deployed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
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
        if (in_array($propertyName, ['ruleName', 'ruleDescription', 'ruleEvent', 'selectedProjectStage', 'ruleStatus', 'recipientTypes', 'selectedRoles', 'selectedUsers'])) {
            $this->markFormAsModified();
        }
    }

    public function resetCreateRuleModal()
    {
        $this->ruleName = '';
        $this->ruleDescription = '';
        $this->ruleEvent = '';
        $this->selectedProjectStage = '';
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

        $event = [
            'class' => $this->ruleEvent,
        ];

        if ($this->ruleEvent === \App\Events\ProjectStageChange::class && $this->selectedProjectStage) {
            $event['project_stage'] = $this->selectedProjectStage;
        }

        NotificationRule::create([
            'name' => $this->ruleName,
            'description' => $this->ruleDescription,
            'event' => $event,
            'recipients' => $recipients,
            'active' => $this->ruleStatus,
        ]);

        Flux::modal('create-rule-modal')->close();
        Flux::toast('Rule created successfully', variant: 'success');
        $this->resetCreateRuleModal();
        $this->markFormAsNotModified();
    }
}
