<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $scheduling = $this->scheduling;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status?->value,
            'school_group' => $this->school_group,
            'assignments' => [
                'assigned_to' => $this->userSummary($scheduling?->assignedUser),
                'technical_lead' => $this->userSummary($scheduling?->technicalLead),
                'change_champion' => $this->userSummary($scheduling?->changeChampion),
                'cose_it_staff' => collect($scheduling->cose_it_staff_users ?? [])
                    ->map(fn (User $u) => $this->userSummary($u))
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function userSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => trim($user->forenames.' '.$user->surname),
        ];
    }
}
