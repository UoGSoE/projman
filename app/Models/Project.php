<?php

namespace App\Models;

use App\Enums\AvailabilityForChange;
use App\Enums\ProjectStatus;
use App\Events\ProjectCreated;
use App\Events\ProjectStageChange;
use App\Models\Traits\CanCheckIfEdited;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class Project extends Model
{
    use CanCheckIfEdited;

    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

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
        'deadline' => 'date',
    ];

    /**
     * Get the validation rules that apply to the model.
     */
    public static function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'school_group' => 'nullable|string|max:255',
            'deadline' => 'nullable|date|after:today',
            'status' => ['required', Rule::enum(ProjectStatus::class)],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'title.required' => 'Project title is required.',
            'title.max' => 'Project title cannot exceed 255 characters.',
            'user_id.exists' => 'Selected user does not exist.',
            'deadline.after' => 'Deadline must be a future date.',
            'status.enum' => 'Invalid project status.',
        ];
    }

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

    public function build(): HasOne
    {
        return $this->hasOne(Build::class);
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

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [
                ProjectStatus::COMPLETED->value,
                ProjectStatus::CANCELLED->value,
            ])
            ->where(function (Builder $query) {
                $query->whereNull('deadline')
                    ->orWhereDate('deadline', '>=', Carbon::today());
            });
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

    public function advanceToNextStage(): ProjectStatus
    {
        $this->update(['status' => $this->status->getNextStatus($this)]);
        ProjectStageChange::dispatch($this, auth()->user());

        return $this->status;
    }

    public function returnToPreviousStage(): ProjectStatus
    {
        $previous = $this->status->getPreviousStatus();

        abort_if($previous === null, 422, 'Cannot return to a previous stage from '.$this->status->label().'.');

        $this->update(['status' => $previous]);

        return $this->status;
    }

    /**
     * IDs of every user allocated to this project across all stage forms.
     */
    public function teamMemberIds(): Collection
    {
        return collect([
            $this->scheduling?->assigned_to,
            $this->scheduling?->technical_lead_id,
            $this->scheduling?->change_champion_id,
            $this->detailedDesign?->designed_by,
            $this->development?->lead_developer,
            $this->testing?->test_lead,
            $this->feasibility?->assessed_by,
            $this->scoping?->assessed_by,
        ])
            ->filter()
            ->merge(collect($this->scheduling?->cose_it_staff ?? []))
            ->merge(collect($this->development?->development_team ?? []))
            ->unique()
            ->values();
    }

    /**
     * Per-day cost (as a fraction of full-time) this project imposes on the
     * given user, distributed equally across allocated people and spread
     * uniformly across the project's working-day duration, normalised against
     * the user's Availability for Change.
     */
    public function perDayCostForUser(User $user): float
    {
        $effortDays = $this->scoping?->estimated_effort?->estimatedDays();
        $start = $this->scheduling?->estimated_start_date;
        $end = $this->scheduling?->estimated_completion_date;

        if (! $effortDays || ! $start || ! $end) {
            return 0.0;
        }

        return self::calculatePerDayCost(
            $user,
            $effortDays,
            $this->teamMemberIds()->count(),
            (int) $start->diffInWeekdays($end) + 1,
        );
    }

    /**
     * Pure formula behind perDayCostForUser. Exposed so the heatmap's live
     * preview can compute the cost of an in-edit project using form values
     * that aren't saved to the database yet.
     */
    public static function calculatePerDayCost(User $user, int $effortDays, int $peopleCount, int $duration): float
    {
        $afc = ($user->availability_for_change ?? AvailabilityForChange::Moderate)->value / 100;

        if ($afc <= 0) {
            return PHP_FLOAT_MAX;
        }

        return $effortDays / max(1, $peopleCount) / max(1, $duration) / $afc;
    }
}
