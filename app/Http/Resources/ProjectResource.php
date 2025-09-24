<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin \App\Models\Project */
class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status?->value ?? $this->status,
            'deadline' => $this->formatDate($this->deadline),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deployed' => $this->whenLoaded('deployed', function () {
                return $this->deployed
                    ? new DeployedResource($this->deployed)
                    : null;
            }),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }
}
