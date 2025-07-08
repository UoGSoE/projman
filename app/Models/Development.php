<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Development extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'deliverable_title',
        'lead_developer',
        'development_team',
        'technical_approach',
        'development_notes',
        'repository_link',
        'status',
        'start_date',
        'completion_date',
        'code_review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
