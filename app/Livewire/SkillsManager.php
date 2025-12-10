<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Ohffs\SimpleSpout\ExcelSheet;

class SkillsManager extends Component
{
    use WithPagination;

    public string $sortColumn = 'name';

    public string $sortDirection = 'asc';

    public string $skillSearchQuery = '';

    public string $userSearchQuery = '';

    public string $activeTab = 'available-skills';

    public bool $showCreateSkillForm = false;

    public ?Skill $selectedSkill = null;

    public ?User $selectedUser = null;

    public ?Skill $selectedSkillForAssignment = null;

    // Skill form fields
    public string $skillName = '';

    public string $skillDescription = '';

    public string $skillCategory = '';

    // User skill assignment fields
    public string $newSkillLevel = '';

    public array $userSkillLevels = [];

    public string $skillSearchForAssignment = '';

    public string $newSkillName = '';

    public string $newSkillDescription = '';

    public string $newSkillCategory = '';

    public function render()
    {
        return view('livewire.skills-manager', [
            'skills' => $this->getSkills(),
            'users' => $this->getStaffUsers(),
            'filteredSkills' => $this->getFilteredSkillsForAssignment(),
            'maxDisplayedSkills' => 3,
            'filteredCategories' => Skill::getAvailableSkillCategories(),
        ]);
    }

    private function getSkills()
    {
        return Skill::getSkillsWithSearch(
            $this->skillSearchQuery,
            $this->sortColumn,
            $this->sortDirection,
            10
        );
    }

    private function getStaffUsers()
    {
        return User::where('is_staff', true)
            ->when(
                strlen($this->userSearchQuery) >= 2,
                function ($query) {
                    $query->where(function ($q) {
                        // Search each word independently to support "John Doe" style searches
                        $words = preg_split('/\s+/', trim($this->userSearchQuery));
                        foreach ($words as $word) {
                            $q->where(function ($inner) use ($word) {
                                $inner->where('forenames', 'like', "%{$word}%")
                                    ->orWhere('surname', 'like', "%{$word}%");
                            });
                        }
                    });
                }
            )
            ->with(['skills' => fn ($query) => $query->orderByRaw(Skill::getSkillLevelOrdering())])
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    private function getFilteredSkillsForAssignment()
    {
        if (strlen($this->skillSearchForAssignment) < 2) {
            return collect();
        }

        return Skill::searchSkill($this->skillSearchForAssignment, 10);
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

    public function updatedSkillSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedSkillSearchForAssignment(): void
    {
        $this->newSkillName = $this->skillSearchForAssignment;
    }

    // Skill CRUD

    public function openAddSkillModal(): void
    {
        $this->selectedSkill = null;
        $this->skillName = '';
        $this->skillDescription = '';
        $this->skillCategory = '';
        Flux::modal('add-skill-form')->show();
    }

    public function openEditSkillModal(Skill $skill): void
    {
        $this->selectedSkill = $skill;
        $this->skillName = $skill->name;
        $this->skillDescription = $skill->description;
        $this->skillCategory = $skill->skill_category;
        Flux::modal('edit-skill-form')->show();
    }

    public function saveSkill(): void
    {
        $validated = $this->validate([
            'skillName' => 'required|string|max:255',
            'skillDescription' => 'required|string|min:3|max:255',
            'skillCategory' => 'required|string|min:3|max:255',
        ]);

        $skillData = [
            'name' => $this->skillName,
            'description' => $this->skillDescription,
            'skill_category' => $this->skillCategory,
        ];

        if ($this->selectedSkill) {
            $this->selectedSkill->update($skillData);
            Flux::toast('Skill updated successfully', variant: 'success');
            Flux::modal('edit-skill-form')->close();
        } else {
            Skill::create($skillData);
            Flux::toast('Skill created successfully', variant: 'success');
            Flux::modal('add-skill-form')->close();
        }
    }

    public function deleteSkill(Skill $skill): void
    {
        if ($skill->isAssignedToUsers()) {
            Flux::toast('Cannot delete skill that is assigned to users', variant: 'danger');

            return;
        }

        $skill->delete();
        Flux::toast('Skill deleted successfully', variant: 'success');
    }

    // User skill management

    public function openUserSkillModal(User $user): void
    {
        $this->selectedUser = $user->load('skills');
        $this->skillSearchForAssignment = '';
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';
        $this->newSkillName = '';
        $this->newSkillDescription = '';
        $this->newSkillCategory = '';
        $this->showCreateSkillForm = false;
        $this->userSkillLevels = $this->selectedUser->skills->pluck('pivot.skill_level', 'id')->toArray();
        Flux::modal('user-skills-form')->show();
    }

    public function toggleSkillSelection(int $skillId): void
    {
        if ($this->selectedSkillForAssignment?->id === $skillId) {
            $this->selectedSkillForAssignment = null;
            $this->newSkillLevel = '';

            return;
        }

        $this->selectedSkillForAssignment = Skill::find($skillId);
        $this->newSkillLevel = SkillLevel::BEGINNER->value;
    }

    public function cancelSkillSelection(): void
    {
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';
    }

    public function addSkillWithLevel(): void
    {
        if (! $this->selectedUser || ! $this->selectedSkillForAssignment || ! $this->newSkillLevel) {
            return;
        }

        $this->validate([
            'newSkillLevel' => ['required', Rule::enum(SkillLevel::class)],
        ]);

        $this->selectedUser->updateSkill($this->selectedSkillForAssignment->id, $this->newSkillLevel);
        $this->refreshSelectedUser();
        $this->userSkillLevels[$this->selectedSkillForAssignment->id] = $this->newSkillLevel;
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';

        Flux::toast('Skill added successfully', variant: 'success');
    }

    public function toggleCreateSkillForm(): void
    {
        $this->showCreateSkillForm = ! $this->showCreateSkillForm;

        if ($this->showCreateSkillForm) {
            $this->newSkillName = $this->skillSearchForAssignment;
            $this->newSkillLevel = SkillLevel::BEGINNER->value;
        } else {
            $this->newSkillName = '';
            $this->newSkillDescription = '';
            $this->newSkillCategory = '';
            $this->newSkillLevel = '';
        }
    }

    public function createAndAssignSkill(): void
    {
        if (! $this->selectedUser) {
            Flux::toast('No user selected', variant: 'danger');

            return;
        }

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

        $this->selectedUser->updateSkill($skill->id, $this->newSkillLevel);
        $this->refreshSelectedUser();
        $this->userSkillLevels[$skill->id] = $this->newSkillLevel;

        $this->newSkillName = '';
        $this->newSkillDescription = '';
        $this->newSkillCategory = '';
        $this->newSkillLevel = '';
        $this->skillSearchForAssignment = '';
        $this->showCreateSkillForm = false;

        Flux::toast('Skill created and assigned successfully', variant: 'success');
    }

    public function updateSkillLevel(int $skillId, string $level): void
    {
        if (! $this->selectedUser || ! User::isValidSkillLevel($level)) {
            if (! User::isValidSkillLevel($level)) {
                Flux::toast('Invalid skill level', variant: 'danger');
            }

            return;
        }

        $this->selectedUser->updateSkill($skillId, $level);
        $this->refreshSelectedUser();
        $this->userSkillLevels[$skillId] = $level;

        Flux::toast('Skill level updated successfully', variant: 'success');
    }

    public function removeUserSkill(int $skillId): void
    {
        if (! $this->selectedUser) {
            return;
        }

        $this->selectedUser->removeSkill($skillId);
        unset($this->userSkillLevels[$skillId]);
        $this->refreshSelectedUser();

        Flux::toast('Skill removed successfully', variant: 'success');
    }

    private function refreshSelectedUser(): void
    {
        $this->selectedUser = $this->selectedUser->fresh(['skills']);
        $this->userSkillLevels = $this->selectedUser->skills->pluck('pivot.skill_level', 'id')->toArray();
    }

    // Export functionality

    public function downloadExcel()
    {
        return $this->downloadExport('xlsx');
    }

    public function downloadCsv()
    {
        return $this->downloadExport('csv');
    }

    private function downloadExport(string $format)
    {
        $isSkillsTab = $this->activeTab === 'available-skills';
        $data = $isSkillsTab ? $this->getSkillsExportData() : $this->getUserSkillsExportData();
        $prefix = $isSkillsTab ? 'skills-export' : 'user-skills-export';
        $filename = $prefix.'-'.now()->format('Y-m-d').'.'.$format;

        if ($format === 'xlsx') {
            $tempPath = (new ExcelSheet)->generate($data);
        } else {
            $tempPath = $this->generateCsv($data);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }

    private function generateCsv(array $data): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'csv_');
        $handle = fopen($tempPath, 'w');

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return $tempPath;
    }

    public function getSkillsExportData(): array
    {
        $headers = [['Name', 'Description', 'Category', 'Users Count']];

        $rows = Skill::withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Skill $skill) => [
                $skill->name,
                $skill->description,
                $skill->skill_category,
                $skill->users_count,
            ])
            ->toArray();

        return array_merge($headers, $rows);
    }

    public function getUserSkillsExportData(): array
    {
        $headers = [['User', 'Skill', 'Skill Level', 'Skill Level (Numeric)', 'Category']];

        $rows = User::where('is_staff', true)
            ->with('skills')
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get()
            ->flatMap(fn (User $user) => $user->skills->map(function (Skill $skill) use ($user) {
                $skillLevel = SkillLevel::from($skill->pivot->skill_level);

                return [
                    $user->full_name,
                    $skill->name,
                    $skillLevel->getDisplayName(),
                    $skillLevel->getNumericValue(),
                    $skill->skill_category,
                ];
            }))
            ->toArray();

        return array_merge($headers, $rows);
    }
}
