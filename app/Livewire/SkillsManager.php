<?php

namespace App\Livewire;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
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

    public function render()
    {
        return view('livewire.skills-manager', [
            'skills' => $this->getSkills(),
            'users' => $this->getStaffUsers(),
            'maxDisplayedSkills' => 3,
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
        return User::itStaff()
            ->when(
                strlen($this->userSearchQuery) >= 2,
                function ($query) {
                    $query->where(function ($q) {
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
            ->with(['skills' => fn ($query) => $query->orderBy('name')])
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
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

        $rows = User::itStaff()
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
