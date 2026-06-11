<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Touches('project')]
#[Fillable('project_id', 'deliverable_title', 'lead_developer', 'development_team', 'technical_approach', 'development_notes', 'repository_link', 'status', 'start_date', 'completion_date', 'code_review_notes')]
class Development extends Model
{
    use CanCheckIfEdited;
    use HasFactory;


    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'completion_date' => 'date',
            'development_team' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function leadDeveloper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_developer');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
