<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkpackageDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deployed_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'environment' => ['sometimes', 'nullable', 'string', Rule::in(['development', 'staging', 'production'])],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'deployed', 'failed', 'rolled_back'])],
            'deployment_date' => ['required', 'date'],
            'version' => ['sometimes', 'nullable', 'string', 'max:255'],
            'production_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'deployment_notes' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'rollback_plan' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'monitoring_notes' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'deployment_sign_off' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'approved', 'rejected']), 'max:255'],
            'operations_sign_off' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'approved', 'rejected']), 'max:255'],
            'user_acceptance_sign_off' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'approved', 'rejected']), 'max:255'],
            'service_delivery_sign_off' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'approved', 'rejected']), 'max:255'],
            'change_advisory_sign_off' => ['sometimes', 'nullable', 'string', Rule::in(['pending', 'approved', 'rejected']), 'max:255'],
        ];
    }

    public function mappedPayload(): array
    {
        $validated = $this->validated();

        $map = [
            'deployed_by' => 'deployed_by',
            'environment' => 'environment',
            'status' => 'status',
            'deployment_date' => 'deployment_date',
            'version' => 'version',
            'production_url' => 'production_url',
            'deployment_notes' => 'deployment_notes',
            'rollback_plan' => 'rollback_plan',
            'monitoring_notes' => 'monitoring_notes',
            'deployment_sign_off' => 'deployment_sign_off',
            'operations_sign_off' => 'operations_sign_off',
            'user_acceptance_sign_off' => 'user_acceptance',
            'service_delivery_sign_off' => 'service_delivery_sign_off',
            'change_advisory_sign_off' => 'change_advisory_sign_off',
        ];

        $payload = [];

        foreach ($map as $inputKey => $column) {
            if (array_key_exists($inputKey, $validated)) {
                $payload[$column] = $validated[$inputKey];
            }
        }

        return $payload;
    }
}
