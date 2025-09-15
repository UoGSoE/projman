<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Validation\Rule;
use App\Models\Skill;

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
        'password',
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
        return $this->forenames . ' ' . $this->surname;
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
}
