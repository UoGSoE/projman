<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Touches('project')]
#[Fillable('project_id', 'deliverable_title', 'designed_by', 'service_function', 'functional_requirements', 'non_functional_requirements', 'hld_design_link', 'approval_delivery', 'approval_operations', 'approval_resilience', 'approval_change_board', 'approval_agb')]
class DetailedDesign extends Model
{
    use CanCheckIfEdited;
    use HasFactory;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designed_by');
    }
}
