<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHistory extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUserNameAttribute(): string
    {
        return $this->user?->full_name ?? 'System';
    }
}
