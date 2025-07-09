<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DetailedDesign extends Model
{
    use HasFactory;
    use CanCheckIfEdited;

    protected $touches = ['project'];

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
