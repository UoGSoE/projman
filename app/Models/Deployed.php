<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployed extends Model
{
    use CanCheckIfEdited;
    use HasFactory;

    protected $fillable = [
        'project_id',
        'deployment_lead_id',
        'service_function',
        'system',
        'fr1',
        'fr2',
        'fr3',
        'nfr1',
        'nfr2',
        'nfr3',
        'bau_operational_wiki',
        'service_resilience_approval',
        'service_resilience_notes',
        'service_operations_approval',
        'service_operations_notes',
        'service_delivery_approval',
        'service_delivery_notes',
        'service_accepted_at',
        'deployment_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'service_accepted_at' => 'datetime',
            'deployment_approved_at' => 'datetime',
        ];
    }

    protected $touches = ['project'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function deploymentLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deployment_lead_id');
    }

    public function isReadyForServiceAcceptance(): bool
    {
        return ! empty($this->deployment_lead_id)
            && ! empty($this->service_function)
            && ! empty($this->system)
            && ! empty($this->fr1)
            && ! empty($this->nfr1)
            && ! empty($this->bau_operational_wiki);
    }

    public function isReadyForApproval(): bool
    {
        return $this->allApprovalsReceived();
    }

    public function allApprovalsReceived(): bool
    {
        return $this->service_resilience_approval === 'approved'
            && $this->service_operations_approval === 'approved'
            && $this->service_delivery_approval === 'approved';
    }

    public function hasServiceAcceptance(): bool
    {
        return $this->service_accepted_at !== null;
    }

    public function needsServiceAcceptance(): bool
    {
        return $this->service_accepted_at === null;
    }

    public function hasDeploymentApproval(): bool
    {
        return $this->deployment_approved_at !== null;
    }

    public function needsDeploymentApproval(): bool
    {
        return $this->deployment_approved_at === null;
    }
}
