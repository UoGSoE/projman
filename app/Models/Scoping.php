<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scoping extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'assessed_by',
        'estimated_effort',
        'in_scope',
        'out_of_scope',
        'assumptions',
        'skills_required',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
