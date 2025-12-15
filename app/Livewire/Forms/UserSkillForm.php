<?php

namespace App\Livewire\Forms;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UserSkillForm extends Form
{
    public ?User $user = null;

    public array $skillLevels = [];

    // For creating new skills inline
    #[Validate('required|string|max:255')]
    public string $newSkillName = '';

    #[Validate('required|string|max:255')]
    public string $newSkillDescription = '';

    #[Validate('required|string|max:255')]
    public string $newSkillCategory = '';

    public ?SkillLevel $newSkillLevel = null;

    public function setUser(User $user): void
    {
        $this->user = $user->load('skills');
        $this->skillLevels = $this->user->skills->pluck('pivot.skill_level', 'id')->toArray();
        $this->clearNewSkillFields();
    }

    public function addSkill(Skill $skill, SkillLevel $level): void
    {
        $this->user->updateSkill($skill->id, $level->value);
        $this->refreshUser();
        $this->skillLevels[$skill->id] = $level->value;
    }

    public function updateLevel(int $skillId, SkillLevel $level): void
    {
        $this->user->updateSkill($skillId, $level->value);
        $this->refreshUser();
        $this->skillLevels[$skillId] = $level->value;
    }

    public function removeSkill(int $skillId): void
    {
        $this->user->removeSkill($skillId);
        unset($this->skillLevels[$skillId]);
        $this->refreshUser();
    }

    public function createAndAssign(): Skill
    {
        $this->validate([
            'newSkillName' => 'required|string|max:255',
            'newSkillDescription' => 'required|string|max:255',
            'newSkillCategory' => 'required|string|max:255',
            'newSkillLevel' => ['required', Rule::enum(SkillLevel::class)],
        ]);

        $skill = Skill::create([
            'name' => $this->newSkillName,
            'description' => $this->newSkillDescription,
            'skill_category' => $this->newSkillCategory,
        ]);

        $this->user->updateSkill($skill->id, $this->newSkillLevel->value);
        $this->refreshUser();
        $this->skillLevels[$skill->id] = $this->newSkillLevel->value;
        $this->clearNewSkillFields();

        return $skill;
    }

    public function clearNewSkillFields(): void
    {
        $this->newSkillName = '';
        $this->newSkillDescription = '';
        $this->newSkillCategory = '';
        $this->newSkillLevel = null;
    }

    private function refreshUser(): void
    {
        $this->user = $this->user->fresh(['skills']);
        $this->skillLevels = $this->user->skills->pluck('pivot.skill_level', 'id')->toArray();
    }
}
