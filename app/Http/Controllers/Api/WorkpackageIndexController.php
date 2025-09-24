<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkpackageIndexController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->with(['deployed' => fn ($query) => $query->select(
                'id',
                'project_id',
                'deployed_by',
                'environment',
                'status',
                'deployment_date',
                'version',
                'production_url',
                'deployment_notes',
                'rollback_plan',
                'monitoring_notes',
                'deployment_sign_off',
                'operations_sign_off',
                'user_acceptance',
                'service_delivery_sign_off',
                'change_advisory_sign_off',
                'updated_at'
            )])
            ->select('id', 'title', 'status', 'deadline', 'updated_at')
            ->when($request->boolean('only_active'), fn ($query) => $query->incomplete())
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15));

        return ProjectResource::collection($projects);
    }
}
