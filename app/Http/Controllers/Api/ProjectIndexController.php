<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectIndexController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        $projects = Project::query()
            ->with([
                'scheduling.assignedUser',
                'scheduling.technicalLead',
                'scheduling.changeChampion',
            ])
            ->orderBy('id')
            ->paginate();

        $coseIds = $projects->getCollection()
            ->pluck('scheduling.cose_it_staff')
            ->filter()
            ->flatten()
            ->unique()
            ->values();

        $coseUsers = User::whereIn('id', $coseIds)->get()->keyBy('id');

        $projects->getCollection()->each(function (Project $project) use ($coseUsers) {
            if ($project->scheduling) {
                $ids = $project->scheduling->cose_it_staff ?? [];
                $project->scheduling->cose_it_staff_users = collect($ids)
                    ->map(fn ($id) => $coseUsers->get($id))
                    ->filter()
                    ->values();
            }
        });

        return ProjectResource::collection($projects);
    }
}
