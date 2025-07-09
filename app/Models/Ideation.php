<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ideation extends Model
{
    /** @use HasFactory<\Database\Factories\IdeationFactory> */
    use HasFactory;
    use CanCheckIfEdited;

    protected $fillable = [
        'project_id',
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
