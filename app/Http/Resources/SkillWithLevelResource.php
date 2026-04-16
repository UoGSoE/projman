<?php

namespace App\Http\Resources;

use App\Enums\SkillLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SkillWithLevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $level = SkillLevel::from($this->pivot->skill_level);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'skill_category' => $this->skill_category,
            'level' => $level->value,
            'level_value' => $level->getNumericValue(),
        ];
    }
}
