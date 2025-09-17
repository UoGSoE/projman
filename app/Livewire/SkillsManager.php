<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class SkillsManager extends Component
{
    use WithPagination;

    // Pagination and sorting
    public string $sortColumn = 'name';

    public string $sortDirection = 'asc';

    public string $skillSearchQuery = '';

    public string $userSearchQuery = '';

    // UI state
    public string $activeTab = 'available-skills';

    public bool $isFormModified = false;

    public bool $showCreateSkillForm = false;

    // Selected entities
    public ?Skill $selectedSkill = null;

    public ?User $selectedUser = null;

    public ?Skill $selectedSkillForAssignment = null;

    // Skill form data
    public string $skillName = '';

    public string $skillDescription = '';

    public string $skillCategory = '';

    // User skill assignment data
    public string $newSkillLevel = '';

    public array $userSkillLevels = [];

    public string $skillSearchForAssignment = '';

    public string $newSkillName = '';

    public string $newSkillDescription = '';

    public string $newSkillCategory = '';

    // Constants
    private const MAX_DISPLAYED_SKILLS = 3;

    private const SKILLS_PER_PAGE = 10;

    private const SEARCH_MIN_LENGTH = 2;

    protected function rules(): array
    {
        return [
            'skillName' => 'required|string|max:255',
            'skillDescription' => 'required|string|min:3|max:255',
            'skillCategory' => 'required|string|max:255',
            'newSkillLevel' => 'required|in:beginner,intermediate,advanced,expert',
            'newSkillName' => 'required|string|max:255',
            'newSkillDescription' => 'required|string|max:255',
            'newSkillCategory' => 'required|string|max:255',
        ];
    }

    protected function skillFormRules(): array
    {
        return [
            'skillName' => 'required|string|max:255',
            'skillDescription' => 'required|string|min:3|max:255',
            'skillCategory' => 'required|string|min:3|max:255',
        ];
    }

    protected function newSkillWithDetailsRules(): array
    {
        return [
            'newSkillName' => 'required|string|max:255',
            'newSkillDescription' => 'required|string|max:255',
            'newSkillCategory' => 'required|string|max:255',
            'newSkillLevel' => 'required|in:beginner,intermediate,advanced,expert',
        ];
    }

    protected function skillLevelRules(): array
    {
        return [
            'newSkillLevel' => 'required|in:beginner,intermediate,advanced,expert',
        ];
    }

    protected function messages(): array
    {
        return [
            'skillName.required' => 'Skill name is required.',
            'skillName.max' => 'Skill name cannot exceed 255 characters.',
            'skillDescription.required' => 'Skill description is required.',
            'skillDescription.max' => 'Skill description cannot exceed 255 characters.',
            'skillCategory.required' => 'Skill category is required.',
            'skillCategory.max' => 'Skill category cannot exceed 255 characters.',
            'newSkillLevel.required' => 'Skill level is required.',
            'newSkillLevel.in' => 'Invalid skill level selected.',
            'newSkillName.required' => 'New skill name is required.',
            'newSkillName.max' => 'New skill name cannot exceed 255 characters.',
            'newSkillDescription.required' => 'New skill description is required.',
            'newSkillDescription.max' => 'New skill description cannot exceed 255 characters.',
            'newSkillCategory.required' => 'New skill category is required.',
            'newSkillCategory.max' => 'New skill category cannot exceed 255 characters.',
        ];
    }

    public function render()
    {
        return view('livewire.skills-manager', [
            'skills' => $this->getSkills(),
            'users' => $this->getStaffUsers(),
            'filteredSkills' => $this->getFilteredSkillsForAssignment(),
            'maxDisplayedSkills' => self::MAX_DISPLAYED_SKILLS,
            'filteredCategories' => $this->getAvailableSkillCategories(),
        ]);
    }

    private function getSkills()
    {
        return Skill::getSkillsWithSearch(
            $this->skillSearchQuery,
            $this->sortColumn,
            $this->sortDirection,
            self::SKILLS_PER_PAGE
        );
    }

    private function getStaffUsers()
    {
        return User::where('is_staff', true)
            ->when(
                strlen($this->userSearchQuery) >= self::SEARCH_MIN_LENGTH,
                fn ($query) => $query->where($this->buildUserSearchQuery())
            )
            ->with(['skills' => fn ($query) => $query->orderByRaw(Skill::getSkillLevelOrdering())])
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    private function getFilteredSkillsForAssignment()
    {
        if (strlen($this->skillSearchForAssignment) < self::SEARCH_MIN_LENGTH) {
            return collect();
        }

        return Skill::searchSkill($this->skillSearchForAssignment, 10);
    }

    private function buildSkillSearchQuery()
    {
        return fn ($query) => $query->where('name', 'like', '%'.$this->skillSearchQuery.'%')
            ->orWhere('description', 'like', '%'.$this->skillSearchQuery.'%')
            ->orWhere('skill_category', 'like', '%'.$this->skillSearchQuery.'%');
    }

    private function buildUserSearchQuery()
    {
        return fn ($query) => $query->where('forenames', 'like', '%'.$this->userSearchQuery.'%')
            ->orWhere('surname', 'like', '%'.$this->userSearchQuery.'%')
            ->orWhereRaw("CONCAT(forenames, ' ', surname) LIKE ?", ['%'.$this->userSearchQuery.'%']);
    }

    private function getAvailableSkillCategories()
    {
        return Skill::getAvailableSkillCategories();
    }

    public function sort(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openAddSkillModal(): void
    {
        $this->selectedSkill = null;
        $this->resetSkillForm();
        $this->markFormAsNotModified();
        Flux::modal('add-skill-form')->show();
    }

    public function openEditSkillModal(Skill $skill): void
    {
        $this->selectedSkill = $skill;
        $this->populateSkillForm($skill);
        $this->markFormAsNotModified();
        Flux::modal('edit-skill-form')->show();
    }

    private function populateSkillForm(Skill $skill): void
    {
        $this->skillName = $skill->name;
        $this->skillDescription = $skill->description;
        $this->skillCategory = $skill->skill_category;
    }

    public function saveSkill(): void
    {
        $this->validate($this->skillFormRules());

        $skillData = $this->getSkillFormData();

        if ($this->selectedSkill) {
            $this->updateExistingSkill($skillData);
        } else {
            $this->createNewSkill($skillData);
        }
    }

    public function deleteSkill(Skill $skill): void
    {
        if ($this->isSkillAssignedToUsers($skill)) {
            Flux::toast('Cannot delete skill that is assigned to users', variant: 'error');

            return;
        }

        $skill->delete();
        Flux::toast('Skill deleted successfully', variant: 'success');
    }

    public function closeAddSkillModal(): void
    {
        $this->selectedSkill = null;
        $this->resetSkillForm();
        $this->markFormAsNotModified();
        Flux::modal('add-skill-form')->close();
    }

    public function closeEditSkillModal(): void
    {
        $this->selectedSkill = null;
        $this->resetSkillForm();
        $this->markFormAsNotModified();
        Flux::modal('edit-skill-form')->close();
    }

    private function getSkillFormData(): array
    {
        return [
            'name' => $this->skillName,
            'description' => $this->skillDescription,
            'skill_category' => $this->skillCategory,
        ];
    }

    private function updateExistingSkill(array $skillData): void
    {
        $this->selectedSkill->update($skillData);
        Flux::toast('Skill updated successfully', variant: 'success');
        $this->closeEditSkillModal();
    }

    private function createNewSkill(array $skillData): void
    {
        Skill::create($skillData);
        Flux::toast('Skill created successfully', variant: 'success');
        $this->closeAddSkillModal();
    }

    private function isSkillAssignedToUsers(Skill $skill): bool
    {
        return $skill->isAssignedToUsers();
    }

    private function resetSkillForm(): void
    {
        $this->skillName = '';
        $this->skillDescription = '';
        $this->skillCategory = '';
    }

    public function openUserSkillModal(User $user): void
    {
        $this->selectedUser = $user->load('skills');
        $this->resetUserSkillForm();
        $this->userSkillLevels = $this->selectedUser->skills->pluck('pivot.skill_level', 'id')->toArray();
        $this->markFormAsNotModified();
        Flux::modal('user-skills-form')->show();

    }

    public function closeUserSkillModal(): void
    {
        $this->selectedUser = null;
        $this->resetUserSkillForm();
        $this->markFormAsNotModified();
        Flux::modal('user-skills-form')->close();
    }

    private function resetUserSkillForm(): void
    {
        $this->skillSearchForAssignment = '';
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';
        $this->userSkillLevels = [];
        $this->newSkillName = '';
        $this->newSkillDescription = '';
        $this->newSkillCategory = '';
        $this->showCreateSkillForm = false;
    }

    private function isValidSkillLevel(string $level): bool
    {
        return User::isValidSkillLevel($level);
    }

    public function markFormAsModified(): void
    {
        $this->isFormModified = true;
    }

    public function markFormAsNotModified(): void
    {
        $this->isFormModified = false;
    }

    public function updatedSkillSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedSkillSearchForAssignment(): void
    {
        $this->newSkillName = $this->skillSearchForAssignment;
    }

    public function updatedSkillName(): void
    {
        $this->markFormAsModified();
    }

    public function updatedSkillDescription(): void
    {
        $this->markFormAsModified();
    }

    public function updatedSkillCategory(): void
    {
        $this->markFormAsModified();
    }

    public function toggleSkillSelection(int $skillId): void
    {
        if ($this->selectedSkillForAssignment && $this->selectedSkillForAssignment->id === $skillId) {
            $this->collapseSkillSelection();
        } else {
            $this->expandSkillSelection($skillId);
        }
    }

    public function cancelSkillSelection(): void
    {
        $this->collapseSkillSelection();
    }

    public function createAndAssignSkill(): void
    {
        if (! $this->selectedUser) {
            Flux::toast('No user selected', variant: 'error');

            return;
        }

        $this->validate($this->newSkillWithDetailsRules());

        $skill = $this->createSkillFromFormData();
        $this->selectedUser->updateSkillForUser($skill, $this->newSkillLevel);
        $this->refreshSelectedUser();
        $this->userSkillLevels[$skill->id] = $this->newSkillLevel;
        $this->clearNewSkillForm();

        Flux::toast('Skill created and assigned successfully', variant: 'success');
    }

    public function toggleCreateSkillForm(): void
    {
        $this->showCreateSkillForm = ! $this->showCreateSkillForm;

        if ($this->showCreateSkillForm) {
            $this->newSkillName = $this->skillSearchForAssignment;
            $this->newSkillLevel = SkillLevel::BEGINNER->value;
        } else {
            $this->clearNewSkillForm();
        }
    }

    public function addSkillWithLevel(): void
    {
        if (! $this->canAddSkill()) {
            return;
        }

        $this->validate($this->skillLevelRules());

        $this->selectedUser->updateSkillForUser($this->selectedSkillForAssignment, $this->newSkillLevel);
        $this->refreshSelectedUser();
        $this->userSkillLevels[$this->selectedSkillForAssignment->id] = $this->newSkillLevel;
        $this->collapseSkillSelection();

        Flux::toast('Skill added successfully', variant: 'success');
    }

    public function updateSkillLevel(int $skillId, string $level): void
    {
        if (! $this->selectedUser || ! $this->isValidSkillLevel($level)) {
            if (! $this->isValidSkillLevel($level)) {
                Flux::toast('Invalid skill level', variant: 'error');
            }

            return;
        }

        $this->selectedUser->updateSkillForUser(Skill::find($skillId), $level);
        $this->refreshSelectedUser();
        $this->userSkillLevels[$skillId] = $level;

        Flux::toast('Skill level updated successfully', variant: 'success');
    }

    public function removeUserSkill(int $skillId): void
    {
        if (! $this->selectedUser) {
            return;
        }

        $this->selectedUser->removeSkillForUser($skillId);
        unset($this->userSkillLevels[$skillId]);
        $this->refreshSelectedUser();

        Flux::toast('Skill removed successfully', variant: 'success');
    }

    private function collapseSkillSelection(): void
    {
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';
    }

    private function expandSkillSelection(int $skillId): void
    {
        $this->selectedSkillForAssignment = Skill::find($skillId);
        $this->newSkillLevel = SkillLevel::BEGINNER->value;
    }

    private function createSkillFromFormData(): Skill
    {
        return Skill::create([
            'name' => $this->newSkillName,
            'description' => $this->newSkillDescription,
            'skill_category' => $this->newSkillCategory,
        ]);
    }

    private function refreshSelectedUser(): void
    {
        $this->selectedUser = $this->selectedUser->fresh(['skills']);
        $this->userSkillLevels = $this->selectedUser->skills->pluck('pivot.skill_level', 'id')->toArray();
    }

    private function clearNewSkillForm(): void
    {
        $this->newSkillName = '';
        $this->newSkillDescription = '';
        $this->newSkillCategory = '';
        $this->newSkillLevel = '';
        $this->skillSearchForAssignment = '';
        $this->showCreateSkillForm = false;
    }

    // check if skill can be added to user (user, skill, level are required to do so)
    private function canAddSkill(): bool
    {
        return $this->selectedUser && $this->selectedSkillForAssignment && $this->newSkillLevel;
    }
}
