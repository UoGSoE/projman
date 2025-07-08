<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\ProjectHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_group',
        'title',
        'deadline',
        'status',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function ideation(): HasOne
    {
        return $this->hasOne(Ideation::class);
    }

    public function feasibility(): HasOne
    {
        return $this->hasOne(Feasibility::class);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('status', '!=', ProjectStatus::COMPLETED);
    }
}
