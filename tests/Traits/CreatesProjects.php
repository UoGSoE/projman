<?php

namespace Tests\Traits;

use App\Models\Project;

trait CreatesProjects
{
    /**
     * Create a project without triggering events.
     * Subforms are created directly, bypassing listeners.
     */
    protected function createProject(array $attributes = []): Project
    {
        return Project::withoutEvents(function () use ($attributes) {
            $project = Project::factory()->create($attributes);
            $this->createSubformsFor($project);

            return $project;
        });
    }

    /**
     * Create a project WITH events (for integration tests that need them).
     */
    protected function createProjectWithEvents(array $attributes = []): Project
    {
        return Project::factory()->create($attributes);
    }

    protected function createSubformsFor(Project $project): void
    {
        foreach (config('projman.subforms') as $formType) {
            $model = new $formType;
            $model->project_id = $project->id;
            $model->saveQuietly();
        }
    }
}
