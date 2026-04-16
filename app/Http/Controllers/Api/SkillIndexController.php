<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SkillIndexController extends Controller
{
    public function __invoke(): ResourceCollection
    {
        return SkillResource::collection(
            Skill::query()->orderBy('name')->paginate()
        );
    }
}
