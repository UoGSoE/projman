<?php

namespace App\Livewire;

use App\Models\Skill;
use App\Models\User;
use App\Services\SkillsSpreadsheetParser;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class SkillsImporter extends Component
{
    use WithFileUploads;

    public $spreadsheet;

    public string $step = 'upload';

    public array $parsedSkills = [];

    public array $parsedStaffSkills = [];

    public array $skippedStaff = [];

    public array $autoMatched = [];

    public array $unmatched = [];

    public array $userSelections = [];

    public array $importSummary = [];

    public function render()
    {
        $notInSystem = collect($this->userSelections)->filter(fn ($v) => $v === 'not_in_system')->keys();

        return view('livewire.skills-importer', [
            'staffUsers' => $this->step === 'preview'
                ? User::where('is_staff', true)->orderBy('surname')->orderBy('forenames')->get()
                : collect(),
            'skillsByCategory' => collect($this->parsedSkills)->groupBy('category')->map->count(),
            'notInSystemCount' => $notInSystem->count(),
            'notInSystemNames' => $notInSystem->join(', '),
        ]);
    }

    public function parseSpreadsheet(): void
    {
        $this->validate([
            'spreadsheet' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $result = (new SkillsSpreadsheetParser)->parse($this->spreadsheet->getRealPath());

        $this->parsedSkills = $result['skills'];
        $this->parsedStaffSkills = $result['staffSkills'];
        $this->skippedStaff = $result['skippedStaff'];

        $this->matchUsersBySurname();

        $this->step = 'preview';
    }

    public function updateUserSelection(string $spreadsheetName, string $value): void
    {
        $this->userSelections[$spreadsheetName] = $value;
    }

    public function confirmImport(): void
    {
        foreach ($this->parsedSkills as $skill) {
            Skill::updateOrCreate(
                ['name' => $skill['name']],
                ['description' => $skill['description'], 'skill_category' => $skill['category']]
            );
        }

        $skillLookup = Skill::pluck('id', 'name');

        $usersUpdated = 0;
        $usersSkipped = 0;

        $resolvedUsers = $this->getResolvedUsers();

        foreach ($resolvedUsers as $spreadsheetName => $userId) {
            $user = User::find($userId);
            if (! $user) {
                $usersSkipped++;

                continue;
            }

            $staffSkills = $this->parsedStaffSkills[$spreadsheetName] ?? [];
            $syncArray = [];

            foreach ($staffSkills as $skillName => $level) {
                $skillId = $skillLookup[$skillName] ?? null;
                if ($skillId) {
                    $syncArray[$skillId] = ['skill_level' => $level];
                }
            }

            $user->skills()->sync($syncArray);
            $usersUpdated++;
        }

        $notInSystem = collect($this->userSelections)->filter(fn ($v) => $v === 'not_in_system')->count();
        $notInSystem += count($this->unmatched) - collect($this->userSelections)->filter(fn ($v) => $v !== 'not_in_system')->count();

        $this->importSummary = [
            'skills_imported' => count($this->parsedSkills),
            'users_updated' => $usersUpdated,
            'users_skipped' => count($this->skippedStaff) + max(0, collect($this->userSelections)->filter(fn ($v) => $v === 'not_in_system')->count()),
        ];

        $this->step = 'complete';

        Flux::toast('Import completed successfully', variant: 'success');
    }

    public function resetImport(): void
    {
        $this->reset();
    }

    private function matchUsersBySurname(): void
    {
        $this->autoMatched = [];
        $this->unmatched = [];
        $this->userSelections = [];

        foreach (array_keys($this->parsedStaffSkills) as $spreadsheetName) {
            $parts = explode(' ', trim($spreadsheetName));
            $surname = array_pop($parts);
            $forenames = implode(' ', $parts);

            $matches = User::where('surname', $surname)
                ->where('is_staff', true)
                ->get();

            if ($matches->count() > 1 && $forenames !== '') {
                $byFullName = $matches->where('forenames', $forenames);

                if ($byFullName->count() === 1) {
                    $matches = $byFullName;
                }
            }

            if ($matches->count() === 1) {
                $user = $matches->first();
                $this->autoMatched[$spreadsheetName] = [
                    'userId' => $user->id,
                    'userName' => $user->full_name,
                ];
            } else {
                $this->unmatched[] = $spreadsheetName;
                $this->userSelections[$spreadsheetName] = 'not_in_system';
            }
        }
    }

    private function getResolvedUsers(): array
    {
        $resolved = [];

        foreach ($this->autoMatched as $spreadsheetName => $match) {
            $resolved[$spreadsheetName] = $match['userId'];
        }

        foreach ($this->userSelections as $spreadsheetName => $value) {
            if ($value !== 'not_in_system') {
                $resolved[$spreadsheetName] = (int) $value;
            }
        }

        return $resolved;
    }
}
