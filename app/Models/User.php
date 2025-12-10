<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Busyness;
use App\Enums\ProjectStatus;
use App\Enums\ServiceFunction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'surname',
        'forenames',
        'is_staff',
        'is_admin',
        'email',
        'service_function',
        'password',
        'busyness_week_1',
        'busyness_week_2',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
            'is_admin' => 'boolean',
            'service_function' => ServiceFunction::class,
            'busyness_week_1' => Busyness::class,
            'busyness_week_2' => Busyness::class,
        ];
    }

    /**
     * Get the validation rules that apply to the model.
     */
    public static function rules(): array
    {
        return [
            'username' => 'required|string|max:255|unique:users,username',
            'surname' => 'required|string|max:255',
            'forenames' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'is_staff' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.unique' => 'Username is already taken.',
            'surname.required' => 'Surname is required.',
            'forenames.required' => 'Forenames are required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function getFirstNameAttribute()
    {
        return explode(' ', $this->forenames)[0];
    }

    public function getFullNameAttribute()
    {
        return $this->forenames.' '.$this->surname;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot('skill_level')
            ->withTimestamps();
    }

    public function updateSkill(int $skillId, string $level): void
    {
        if ($level == 'none') {
            $this->removeSkill($skillId);

            return;
        }

        $this->skills()->syncWithoutDetaching([
            $skillId => ['skill_level' => $level],
        ]);

    }

    /**
     * Remove a skill from this user.
     */
    public function removeSkill(int $skillId): void
    {
        $this->skills()->detach($skillId);
    }

    /**
     * Check if a skill level is valid.
     */
    public static function isValidSkillLevel(string $level): bool
    {
        return in_array($level, \App\Enums\SkillLevel::getAll());
    }

    public function getSkillLevel(Skill $skill): string
    {
        $currentSkill = $this->skills()->where('skill_id', $skill->id)->first();
        if (! $currentSkill) {
            return 'none';
        }

        return $currentSkill->pivot->skill_level;
    }

    public function getBusynessWeek1Attribute($value): ?Busyness
    {
        return $value ? Busyness::from($value) : null;
    }

    public function setBusynessWeek1Attribute($value): void
    {
        $this->attributes['busyness_week_1'] = $value instanceof Busyness ? $value->value : $value;
    }

    public function getBusynessWeek2Attribute($value): ?Busyness
    {
        return $value ? Busyness::from($value) : null;
    }

    public function setBusynessWeek2Attribute($value): void
    {
        $this->attributes['busyness_week_2'] = $value instanceof Busyness ? $value->value : $value;
    }

    /**
     * Count active projects this user is assigned to work on.
     *
     * Checks all assignment fields: assigned_to, technical_lead_id,
     * change_champion_id, and cose_it_staff JSON array.
     */
    public function activeAssignedProjectCount(): int
    {
        return Project::query()
            ->whereNotIn('status', [
                ProjectStatus::COMPLETED->value,
                ProjectStatus::CANCELLED->value,
            ])
            ->whereHas('scheduling', function ($query) {
                $query->where('assigned_to', $this->id)
                    ->orWhere('technical_lead_id', $this->id)
                    ->orWhere('change_champion_id', $this->id)
                    ->orWhereJsonContains('cose_it_staff', $this->id);
            })
            ->count();
    }
}
