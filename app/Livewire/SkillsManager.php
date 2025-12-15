<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Livewire\Forms\SkillForm;
use App\Livewire\Forms\UserSkillForm;
use App\Models\Skill;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Ohffs\SimpleSpout\ExcelSheet;

class SkillsManager extends Component
{
    use WithPagination;

    public SkillForm $skillForm;

    public UserSkillForm $userSkillForm;

    public string $sortColumn = 'name';

    public string $sortDirection = 'asc';

    public string $skillSearchQuery = '';

    public string $userSearchQuery = '';

    public string $activeTab = 'available-skills';

    // UI state for user skill modal
    public bool $showCreateSkillForm = false;

    public ?Skill $selectedSkillForAssignment = null;

    public string $skillSearchForAssignment = '';

    public string $newSkillLevel = '';

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
        $this->userSkillForm->newSkillName = $this->skillSearchForAssignment;
    }

    // Skill CRUD

    public function openAddSkillModal(): void
    {
        $this->skillForm->clear();
        Flux::modal('add-skill-form')->show();
    }

    public function openEditSkillModal(Skill $skill): void
    {
        $this->skillForm->setSkill($skill);
        Flux::modal('edit-skill-form')->show();
    }

    public function saveSkill(): void
    {
        $this->skillForm->save();

        if ($this->skillForm->isEditing()) {
            Flux::toast('Skill updated successfully', variant: 'success');
            Flux::modal('edit-skill-form')->close();
        } else {
            Flux::toast('Skill created successfully', variant: 'success');
            Flux::modal('add-skill-form')->close();
        }

        $this->skillForm->clear();
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
        $this->userSkillForm->setUser($user);
        $this->skillSearchForAssignment = '';
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';
        $this->showCreateSkillForm = false;
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
        $this->userSkillForm->addSkill(
            $this->selectedSkillForAssignment,
            SkillLevel::from($this->newSkillLevel)
        );
        $this->selectedSkillForAssignment = null;
        $this->newSkillLevel = '';

        Flux::toast('Skill added successfully', variant: 'success');
    }

    public function toggleCreateSkillForm(): void
    {
        $this->showCreateSkillForm = ! $this->showCreateSkillForm;

        if ($this->showCreateSkillForm) {
            $this->userSkillForm->newSkillName = $this->skillSearchForAssignment;
            $this->userSkillForm->newSkillLevel = SkillLevel::BEGINNER;
        } else {
            $this->userSkillForm->clearNewSkillFields();
        }
    }

    public function createAndAssignSkill(): void
    {
        $this->userSkillForm->createAndAssign();
        $this->skillSearchForAssignment = '';
        $this->showCreateSkillForm = false;

        Flux::toast('Skill created and assigned successfully', variant: 'success');
    }

    public function updateSkillLevel(int $skillId, string $level): void
    {
        $this->userSkillForm->updateLevel($skillId, SkillLevel::from($level));

        Flux::toast('Skill level updated successfully', variant: 'success');
    }

    public function removeUserSkill(int $skillId): void
    {
        $this->userSkillForm->removeSkill($skillId);

        Flux::toast('Skill removed successfully', variant: 'success');
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
