<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SkillUsersController extends Controller
{
    public function __invoke(Skill $skill): ResourceCollection
    {
        $users = User::query()
            ->whereHas('skills', fn ($q) => $q->where('skills.id', $skill->id))
            ->with(['skills' => fn ($q) => $q->where('skills.id', $skill->id)])
            ->orderBy('surname')
            ->paginate();

        return UserResource::collection($users);
    }
}
