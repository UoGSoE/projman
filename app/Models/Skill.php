<?php

namespace App\Models;

use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\Rule;

class Skill extends Model
{
    /** @use HasFactory<\Database\Factories\SkillFactory> */
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'skill_category', //TODO: Thinking about it
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
            'skill_category' => 'string'
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
}
