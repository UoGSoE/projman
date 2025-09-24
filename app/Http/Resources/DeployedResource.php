<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin \App\Models\Deployed */
class DeployedResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'deployed_by' => $this->deployed_by,
            'environment' => $this->environment,
            'status' => $this->status,
            'deployment_date' => $this->formatDate($this->deployment_date),
            'version' => $this->version,
            'production_url' => $this->production_url,
            'deployment_notes' => $this->deployment_notes,
            'rollback_plan' => $this->rollback_plan,
            'monitoring_notes' => $this->monitoring_notes,
            'deployment_sign_off' => $this->deployment_sign_off,
            'operations_sign_off' => $this->operations_sign_off,
            'user_acceptance_sign_off' => $this->user_acceptance,
            'service_delivery_sign_off' => $this->service_delivery_sign_off,
            'change_advisory_sign_off' => $this->change_advisory_sign_off,
            'updated_at' => $this->updated_at?->toIso8601String(),
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
