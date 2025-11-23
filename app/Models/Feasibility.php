<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feasibility extends Model
{
    use CanCheckIfEdited;

    /** @use HasFactory<\Database\Factories\FeasibilityFactory> */
    use HasFactory;

    protected $touches = ['project'];

    protected $fillable = [
        'project_id',
        'assessed_by',
        'date_assessed',
        'technical_credence',
        'cost_benefit_case',
        'dependencies_prerequisites',
        'deadlines_achievable',
        'alternative_proposal',
        'existing_solution_status',
        'existing_solution_notes',
        'off_the_shelf_solution_status',
        'off_the_shelf_solution_notes',
        'reject_reason',
        'approval_status',
        'approved_at',
        'actioned_by',
    ];

    protected $casts = [
        'date_assessed' => 'date',
        'deadlines_achievable' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function isReadyForApproval(): bool
    {
        return filled($this->assessed_by)
            && filled($this->date_assessed)
            && filled($this->technical_credence)
            && filled($this->cost_benefit_case)
            && filled($this->dependencies_prerequisites)
            && filled($this->alternative_proposal);
    }

    public function hasProperSolutionAssessment(): bool
    {
        return filled($this->existing_solution_status)
            || filled($this->off_the_shelf_solution_status);
    }
}
