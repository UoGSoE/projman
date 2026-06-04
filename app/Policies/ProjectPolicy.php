<?php

namespace App\Policies;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id
            || $user->isAdmin()
            || $user->isItStaff();
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin() || $user->isItStaff()) {
            return true;
        }

        return $user->id === $project->user_id
            && $project->status === ProjectStatus::IDEATION;
    }

    public function manageWorkflow(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->isItStaff();
    }

    public function cancel(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->isAdmin();
    }

    public function saveForm(User $user, Project $project, string $formType): bool
    {
        if ($user->isAdmin() || $user->isItStaff()) {
            return true;
        }

        return $user->id === $project->user_id
            && $project->status === ProjectStatus::IDEATION
            && $formType === ProjectStatus::IDEATION->value;
    }
}
