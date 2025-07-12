<?php

namespace App\Listeners;

use App\Events\ProjectCreated;

class CreateRelatedForms
{
    public function handle(ProjectCreated $event): void
    {
        foreach (config('projman.subforms') as $formType) {
            $form = new $formType();
            $form->project_id = $event->project->id;
            $form->save();
        }
    }
}
