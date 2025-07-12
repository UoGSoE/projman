<?php

namespace App\Models;

use App\Models\Scoping;
use App\Models\Testing;
use App\Models\Deployed;
use App\Models\Scheduling;
use App\Models\Development;
use App\Enums\ProjectStatus;
use App\Events\ProjectCreated;
use App\Models\DetailedDesign;
use App\Models\ProjectHistory;
use App\Models\Traits\CanCheckIfEdited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;
    use CanCheckIfEdited;

    protected $dispatchesEvents = [
        'created' => ProjectCreated::class,
    ];

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
        return $this->hasMany(ProjectHistory::class)->orderBy('created_at', 'desc');
    }

    public function ideation(): HasOne
    {
        return $this->hasOne(Ideation::class);
    }

    public function feasibility(): HasOne
    {
        return $this->hasOne(Feasibility::class);
    }

    public function scoping(): HasOne
    {
        return $this->hasOne(Scoping::class);
    }

    public function scheduling(): HasOne
    {
        return $this->hasOne(Scheduling::class);
    }

    public function detailedDesign(): HasOne
    {
        return $this->hasOne(DetailedDesign::class);
    }

    public function development(): HasOne
    {
        return $this->hasOne(Development::class);
    }

    public function testing(): HasOne
    {
        return $this->hasOne(Testing::class);
    }

    public function deployed(): HasOne
    {
        return $this->hasOne(Deployed::class);
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNotIn('status', [
            ProjectStatus::COMPLETED->value,
            ProjectStatus::CANCELLED->value,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ProjectStatus::COMPLETED->value);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', ProjectStatus::CANCELLED->value);
    }

    public function cancel()
    {
        $this->update(['status' => ProjectStatus::CANCELLED]);
    }

    public function isCancelled()
    {
        return $this->status === ProjectStatus::CANCELLED;
    }

    public function addHistory(?User $user, string $description)
    {
        $this->history()->create([
            'user_id' => $user?->id,
            'description' => $description,
        ]);
    }
}
