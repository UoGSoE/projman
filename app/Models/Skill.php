<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    /** @use HasFactory<\Database\Factories\SkillFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'skill_category',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'skill_category' => 'string',
        ];
    }

    /**
     * Get the validation rules that apply to the model.
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'skill_category' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 255 characters.',
            'skill_category.required' => 'Skill category is required.',
            'skill_category.max' => 'Skill category cannot exceed 255 characters.',
        ];
    }

    /**
     * The users that belong to this skill.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('skill_level')
            ->withTimestamps();
    }

    /**
     * Check if this skill is assigned to any users.
     */
    public function isAssignedToUsers(): bool
    {
        return $this->users()->exists();
    }

    /**
     * Search skills by name, description, or category.
     */
    public static function searchSkill(string $query, int $limit = 10)
    {
        return static::where('name', 'like', '%'.$query.'%')
            ->orWhere('description', 'like', '%'.$query.'%')
            ->orWhere('skill_category', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all available skill categories.
     */
    public static function getAvailableSkillCategories()
    {
        return static::distinct()
            ->whereNotNull('skill_category')
            ->where('skill_category', '!=', '')
            ->pluck('skill_category')
            ->sort()
            ->values();
    }

    /**
     * Get skills with user count and search functionality.
     */
    public static function getSkillsWithSearch(string $searchQuery = '', string $sortColumn = 'name', string $sortDirection = 'asc', int $perPage = 10)
    {
        return static::withCount('users')
            ->orderBy($sortColumn, $sortDirection)
            ->when(
                strlen($searchQuery) >= 2,
                fn ($query) => $query->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', '%'.$searchQuery.'%')
                        ->orWhere('description', 'like', '%'.$searchQuery.'%')
                        ->orWhere('skill_category', 'like', '%'.$searchQuery.'%');
                })
            )
            ->paginate($perPage);
    }

    /**
     * Get SQL ordering for skill levels.
     */
    public static function getSkillLevelOrdering(): string
    {
        return "CASE
            WHEN skill_level = 'expert' THEN 1
            WHEN skill_level = 'advanced' THEN 2
            WHEN skill_level = 'intermediate' THEN 3
            WHEN skill_level = 'beginner' THEN 4
            ELSE 5
        END";
    }
}
