<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillWithLevelResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserSkillsController extends Controller
{
    public function __invoke(User $user): ResourceCollection
    {
        $skills = $user->skills()->orderBy('name')->paginate();

        return SkillWithLevelResource::collection($skills);
    }
}
