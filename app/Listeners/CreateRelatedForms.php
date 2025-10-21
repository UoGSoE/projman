<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use Illuminate\Support\Facades\Log;

class CreateRelatedForms
{
    public function handle(ProjectCreated $event): void
    {
        Log::info('Creating related forms');
        foreach (config('projman.subforms') as $formType) {
            $form = new $formType;
            $form->project_id = $event->project->id;
            $form->save();
        }
    }
}
