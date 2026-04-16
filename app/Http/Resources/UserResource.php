<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'forenames' => $this->forenames,
            'surname' => $this->surname,
            'email' => $this->email,
            'is_staff' => (bool) $this->is_staff,
            'service_function' => $this->service_function?->value,
            'busyness_week_1' => $this->busyness_week_1 ? strtolower($this->busyness_week_1->name) : null,
            'busyness_week_1_value' => $this->busyness_week_1?->value,
            'busyness_week_2' => $this->busyness_week_2 ? strtolower($this->busyness_week_2->name) : null,
            'busyness_week_2_value' => $this->busyness_week_2?->value,
            'skills' => SkillWithLevelResource::collection($this->whenLoaded('skills')),
        ];
    }
}
