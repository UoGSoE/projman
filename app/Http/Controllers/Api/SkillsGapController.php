<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;

class SkillsGapController extends Controller
{
    public function __invoke(): array
    {
        $skills = Skill::query()
            ->with(['users' => fn ($q) => $q->itStaff()])
            ->orderBy('name')
            ->get();

        return [
            'data' => $skills->map(function (Skill $skill) {
                $counts = $skill->levelCounts();

                return [
                    'skill_id' => $skill->id,
                    'skill_name' => $skill->name,
                    'skill_category' => $skill->skill_category,
                    'counts' => $counts,
                    'total' => array_sum($counts),
                ];
            })->all(),
        ];
    }
}
