<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class ProjectExportController extends Controller
{
    public function __invoke(Project $project): View
    {
        $project->load([
            'user',
            'ideation',
            'feasibility.assessor',
            'feasibility.actionedBy',
            'scoping',
            'scheduling.assignedUser',
            'scheduling.technicalLead',
            'scheduling.changeChampion',
            'detailedDesign',
            'development.leadDeveloper',
            'build',
            'testing.uatTester',
            'testing.testLead',
            'deployed.deploymentLead',
        ]);

        return view('exports.work-package', [
            'project' => $project,
            'exportDate' => now(),
        ]);
    }
}
