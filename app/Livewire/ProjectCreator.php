<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Project;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;

class ProjectCreator extends Component
{
    #[Validate('required|string|max:255')]
    public string $projectName = '';

    protected array $formTypes = [
        \App\Models\Ideation::class,
        \App\Models\Feasibility::class,
        \App\Models\Testing::class,
        \App\Models\Deployed::class,
        \App\Models\Scoping::class,
        \App\Models\Scheduling::class,
        \App\Models\Development::class,
        \App\Models\DetailedDesign::class,
    ];

    public function render()
    {
        return view('livewire.project-creator');
    }

    public function save()
    {
        $project = Project::create([
            'user_id' => Auth::id(),
            'title' => $this->projectName,
        ]);

        foreach ($this->formTypes as $formType) {
            $form = new $formType();
            $form->project_id = $project->id;
            $form->save();
        }

        $project->addHistory(Auth::user(), 'Created');

        return $this->redirect(route('project.edit', $project->id));
    }
}
