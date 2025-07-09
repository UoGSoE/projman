<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feasibility extends Model
{
    /** @use HasFactory<\Database\Factories\FeasibilityFactory> */
    use HasFactory;
    use CanCheckIfEdited;

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
    ];

    protected $casts = [
        'date_assessed' => 'date',
        'deadlines_achievable' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
