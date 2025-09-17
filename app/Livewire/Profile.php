<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Models\Skill;
use Livewire\Component;

class Profile extends Component
{
    public string $skillSearchQuery = '';

    private const SEARCH_MIN_LENGTH = 2;

    public array $userSkill = [];

    public bool $showMySkills = false;

    public function render()
    {
        return view('livewire.profile', [
            'skillLevels' => SkillLevel::cases(),
            'allSkills' => $this->getAllSkills(),
        ]);
    }

    public function mount()
    {
        $this->buildSkillArray();
    }

    private function getAllSkills()
    {
        if ($this->showMySkills) {
            $query = auth()->user()->skills();
        } else {
            $query = Skill::query();
        }
        $skills = $query->when(strlen($this->skillSearchQuery) >= self::SEARCH_MIN_LENGTH, function ($query) {
            $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->skillSearchQuery.'%')
                    ->orWhere('description', 'like', '%'.$this->skillSearchQuery.'%');
            });
        })->orderBy('name')->get();

        return $skills;
    }

    public function updatedSkillSearchQuery(): void
    {
        // Search query updated - no pagination to reset
    }

    public function buildSkillArray()
    {
        $user = auth()->user();
        $skills = Skill::orderBy('name')->get();
        foreach ($skills as $skill) {
            $this->userSkill[$skill->id] = [
                'skill_level' => $user->getSkillLevel($skill),
            ];
        }

        return $this->userSkill;
    }

    public function updateUserSkill(int $skillId): void
    {
        $skillLevel = $this->userSkill[$skillId]['skill_level'];
        auth()->user()->updateSkill($skillId, $skillLevel);
    }
}
