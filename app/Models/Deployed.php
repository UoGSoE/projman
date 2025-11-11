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
        'deliverable_title',
        'deployed_by',
        'environment',
        'status',
        'deployment_date',
        'version',
        'production_url',
        'deployment_notes',
        'rollback_plan',
        'monitoring_notes',
        'deployment_sign_off',
        'operations_sign_off',
        'user_acceptance',
        'service_delivery_sign_off',
        'change_advisory_sign_off',
    ];

    protected $casts = [
        'deployment_date' => 'date',
    ];

    protected $touches = ['project'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
