<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailedDesign extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'designed_by',
        'service_function',
        'functional_requirements',
        'non_functional_requirements',
        'hld_design_link',
        'approval_delivery',
        'approval_operations',
        'approval_resilience',
        'approval_change_board',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
