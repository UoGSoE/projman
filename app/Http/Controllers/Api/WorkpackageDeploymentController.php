<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkpackageDeploymentRequest;
use App\Http\Resources\DeployedResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkpackageDeploymentController extends Controller
{
    public function __invoke(WorkpackageDeploymentRequest $request, Project $project): JsonResponse
    {
        $project->loadMissing('deployed');

        $deployed = $project->deployed;

        if (! $deployed) {
            throw new NotFoundHttpException('Deployment record not found.');
        }

        $deployed->setRelation('project', $project);

        $deployed->update($request->mappedPayload());

        return response()->json([
            'message' => 'Deployment details updated.',
            'deployed' => new DeployedResource($deployed->refresh()),
        ]);
    }
}
