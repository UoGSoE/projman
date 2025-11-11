<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ideation extends Model
{
    use CanCheckIfEdited;

    /** @use HasFactory<\Database\Factories\IdeationFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'school_group',
        'objective',
        'business_case',
        'benefits',
        'deadline',
        'strategic_initiative',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    protected $touches = ['project'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
