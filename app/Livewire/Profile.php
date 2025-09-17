<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Models\Skill;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class Profile extends Component
{
    use WithPagination;

    public string $skillSearchQuery = '';

    public string $selectedCategory = '';

    public array $userSkillLevels = [];

    public string $addSkillLevel = '';

    private const SKILLS_PER_PAGE = 12;

    private const SEARCH_MIN_LENGTH = 2;

    protected function rules(): array
    {
        return [
            'addSkillLevel' => 'required|in:beginner,intermediate,advanced,expert',
        ];
    }

    protected function messages(): array
    {
        return [
            'addSkillLevel.required' => 'Skill level is required.',
            'addSkillLevel.in' => 'Invalid skill level selected.',
        ];
    }

    public function render()
    {
        $userSkills = $this->getUserSkills();
        $this->userSkillLevels = $userSkills->pluck('pivot.skill_level', 'id')->toArray();

        return view('livewire.profile', [
            'userSkills' => $userSkills,
            'availableSkills' => $this->getAvailableSkills(),
            'skillCategories' => $this->getSkillCategories(),
            'skillLevels' => SkillLevel::cases(),
        ]);
    }

    private function getUserSkills()
    {

        return auth()->user()->skills()
            ->orderByRaw(Skill::getSkillLevelOrdering())
            ->get();
    }

    private function getAvailableSkills()
    {
        $query = Skill::query();

        // Apply search filter
        if (strlen($this->skillSearchQuery) >= self::SEARCH_MIN_LENGTH) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->skillSearchQuery.'%')
                    ->orWhere('description', 'like', '%'.$this->skillSearchQuery.'%')
                    ->orWhere('skill_category', 'like', '%'.$this->skillSearchQuery.'%');
            });
        }

        // Apply category filter
        if ($this->selectedCategory) {
            $query->where('skill_category', $this->selectedCategory);
        }

        // Exclude skills already assigned to user
        $userSkillIds = auth()->user()->skills()->pluck('skills.id');
        if ($userSkillIds->isNotEmpty()) {
            $query->whereNotIn('id', $userSkillIds);
        }

        return $query->orderBy('name')
            ->paginate(self::SKILLS_PER_PAGE);
    }

    private function getSkillCategories()
    {
        return Skill::getAvailableSkillCategories();
    }

    public function updatedSkillSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCategory(): void
    {
        $this->resetPage();
    }

    public function updateSkillLevel(int $skillId, string $level): void
    {
        auth()->user()->updateSkillForUser(Skill::find($skillId), $level);
        $this->userSkillLevels[$skillId] = $level;
        Flux::toast('Skill level updated successfully', variant: 'success');
    }

    public function removeSkill(Skill $skill): void
    {
        auth()->user()->removeSkillForUser($skill->id);
        unset($this->userSkillLevels[$skill->id]);
        Flux::toast('Skill removed successfully', variant: 'success');
    }

    public function addSkillWithLevel(int $skillId): void
    {
        $this->validate(['addSkillLevel' => 'required|in:beginner,intermediate,advanced,expert']);

        $skill = Skill::find($skillId);
        if ($skill) {
            auth()->user()->updateSkillForUser($skill, $this->addSkillLevel);
            $this->userSkillLevels[$skillId] = $this->addSkillLevel;
            Flux::toast('Skill added successfully', variant: 'success');
            $this->addSkillLevel = '';
        }
        $this->skillLevelPopover = false;
    }
}
