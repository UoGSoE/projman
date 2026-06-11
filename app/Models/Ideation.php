<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Database\Factories\IdeationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('project_id', 'school_group', 'objective', 'business_case', 'benefits', 'deadline', 'strategic_initiative')]
#[Touches('project')]
class Ideation extends Model
{
    use CanCheckIfEdited;

    /** @use HasFactory<IdeationFactory> */
    use HasFactory;

    protected $casts = [
        'deadline' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
