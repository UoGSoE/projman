<?php

namespace App\Models;

use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Scoping extends Model
{
    use HasFactory;
    use CanCheckIfEdited;

    protected $touches = ['project'];

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

    protected function casts(): array
    {
        return [
            'skills_required' => 'array',
        ];
    }
}
