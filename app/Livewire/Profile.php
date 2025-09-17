<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\Skill;
use App\Enums\SkillLevel;
use Livewire\Component;
use Livewire\WithPagination;

class Profile extends Component
{
    use WithPagination;

    public string $skillSearchQuery = '';
    public string $selectedCategory = '';

    // Selected skill for level update
    public ?Skill $selectedSkill = null;
    public string $newSkillLevel = '';

    public string $addSkillLevel = '';

    // Constants
    private const SKILLS_PER_PAGE = 12;
    private const SEARCH_MIN_LENGTH = 2;

    protected function rules(): array
    {
        return [
            'newSkillLevel' => 'required|in:beginner,intermediate,advanced,expert',
            'addSkillLevel' => 'required|in:beginner,intermediate,advanced,expert',
        ];
    }

    protected function messages(): array
    {
        return [
            'newSkillLevel.required' => 'Skill level is required.',
            'newSkillLevel.in' => 'Invalid skill level selected.',
            'addSkillLevel.required' => 'Skill level is required.',
            'addSkillLevel.in' => 'Invalid skill level selected.',
        ];
    }

    public function render()
    {
        return view('livewire.profile', [
            'userSkills' => $this->getUserSkills(),
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
                $q->where('name', 'like', '%' . $this->skillSearchQuery . '%')
                    ->orWhere('description', 'like', '%' . $this->skillSearchQuery . '%')
                    ->orWhere('skill_category', 'like', '%' . $this->skillSearchQuery . '%');
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

    public function openUpdateSkillLevelModal(Skill $skill): void
    {
        $this->selectedSkill = $skill;
        $this->newSkillLevel = $skill->pivot->skill_level;
        Flux::modal('update-skill-level')->show();
    }

    public function updateSkillLevel(): void
    {
        $this->validate(['newSkillLevel' => 'required|in:beginner,intermediate,advanced,expert']);

        if ($this->selectedSkill) {
            auth()->user()->updateSkillForUser($this->selectedSkill, $this->newSkillLevel);
            Flux::toast('Skill level updated successfully', variant: 'success');
            $this->closeUpdateSkillLevelModal();
        }
    }

    public function closeUpdateSkillLevelModal(): void
    {
        $this->selectedSkill = null;
        $this->newSkillLevel = '';
        Flux::modal('update-skill-level')->close();
    }

    public function removeSkill(Skill $skill): void
    {
        auth()->user()->removeSkillForUser($skill->id);
        Flux::toast('Skill removed successfully', variant: 'success');
    }

    public function confirmAddExistingSkill(): void
    {
        $this->validate(['addSkillLevel' => 'required|in:beginner,intermediate,advanced,expert']);

        if ($this->selectedSkill) {
            auth()->user()->updateSkillForUser($this->selectedSkill, $this->addSkillLevel);
            Flux::toast('Skill added successfully', variant: 'success');
            $this->closeAddExistingSkillModal();
        }
    }

    public function addSkillWithLevel(int $skillId): void
    {
        $this->validate(['addSkillLevel' => 'required|in:beginner,intermediate,advanced,expert']);

        $skill = Skill::find($skillId);
        if ($skill) {
            auth()->user()->updateSkillForUser($skill, $this->addSkillLevel);
            Flux::toast('Skill added successfully', variant: 'success');
            $this->addSkillLevel = ''; // Reset the skill level
        }
    }

    public function closeAddExistingSkillModal(): void
    {
        $this->selectedSkill = null;
        $this->addSkillLevel = '';
        Flux::modal('add-existing-skill')->close();
    }
}
