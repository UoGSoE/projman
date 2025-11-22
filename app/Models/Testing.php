<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testing extends Model
{
    use CanCheckIfEdited;
    use HasFactory;

    protected $touches = ['project'];

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'test_lead',
        'uat_tester_id',
        'department_office',
        'uat_requested_at',
        'service_function',
        'functional_testing_title',
        'functional_tests',
        'non_functional_testing_title',
        'non_functional_tests',
        'test_repository',
        'testing_sign_off',
        'testing_sign_off_notes',
        'user_acceptance',
        'user_acceptance_notes',
        'testing_lead_sign_off',
        'testing_lead_sign_off_notes',
        'service_delivery_sign_off',
        'service_delivery_sign_off_notes',
        'service_resilience_sign_off',
        'service_resilience_sign_off_notes',
        'uat_approval_status',
        'uat_approved_at',
        'service_acceptance_status',
        'service_accepted_at',
        'service_acceptance_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'uat_requested_at' => 'datetime',
            'uat_approved_at' => 'datetime',
            'service_accepted_at' => 'datetime',
            'service_acceptance_requested_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uatTester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uat_tester_id');
    }

    public function isReadyForServiceAcceptance(): bool
    {
        return $this->user_acceptance === 'approved';
    }

    public function isReadyForSubmit(): bool
    {
        return $this->testing_sign_off === 'approved'
            && $this->user_acceptance === 'approved'
            && $this->testing_lead_sign_off === 'approved'
            && $this->service_delivery_sign_off === 'approved'
            && $this->service_resilience_sign_off === 'approved';
    }
}
