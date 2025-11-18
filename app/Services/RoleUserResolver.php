<?php

namespace App\Services;

use App\Events\ProjectStageChange;
use App\Models\Role;
use Illuminate\Support\Collection;

class RoleUserResolver
{
    public function forEvent(object $event): Collection
    {
        $eventClass = get_class($event);
        $config = config('projman.notifications')[$eventClass] ?? null;

        if (! $config) {
            throw new \Exception('Notification config not found for event: '.$eventClass);
        }

        $users = collect();

        // Handle stage-specific roles for ProjectStageChange
        if ($event instanceof ProjectStageChange) {
            $users = $users->merge($this->resolveStageRoles($event, $config));
        }

        // Handle regular role-based recipients
        if (! empty($config['roles'])) {
            $users = $users->merge($this->resolveRoles($config['roles']));
        }

        // Include project owner if configured
        if (! empty($config['include_project_owner']) && $event->project->user) {
            $users->push($event->project->user);
        }

        return $users->unique('id')->values();
    }

    protected function resolveStageRoles(ProjectStageChange $event, array $config): Collection
    {
        $currentStage = $event->project->status->value;

        if (empty($config['stage_roles'][$currentStage])) {
            return collect();
        }

        return $this->resolveRoles($config['stage_roles'][$currentStage]);
    }

    protected function resolveRoles(array $roleNames): Collection
    {
        return Role::whereIn('name', $roleNames)
            ->with('users')
            ->get()
            ->pluck('users')
            ->flatten();
    }
}
