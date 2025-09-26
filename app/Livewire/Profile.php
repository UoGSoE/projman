<?php

namespace App\Livewire;

use App\Enums\Busyness;
use App\Enums\SkillLevel;
use App\Models\Skill;
use Livewire\Component;

class Profile extends Component
{
    public string $skillSearchQuery = '';

    private const SEARCH_MIN_LENGTH = 2;

    public array $userSkill = [];

    public bool $showMySkills = false;

    public ?Busyness $busynessWeek1 = null;

    public ?Busyness $busynessWeek2 = null;

    public string $week1Start = '';

    public string $week1End = '';

    public string $week2Start = '';

    public string $week2End = '';

    public function render()
    {
        return view('livewire.profile', [
            'skillLevels' => SkillLevel::cases(),
            'allSkills' => $this->getAllSkills(),
            'busynessOptions' => Busyness::cases(),
        ]);
    }

    public function mount()
    {
        $this->buildSkillArray();
        $this->loadBusynessData();
        $this->calculateWeekRanges();
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

    public function loadBusynessData(): void
    {
        $user = auth()->user();
        $this->busynessWeek1 = $user->busyness_week_1 ?? Busyness::LOW;
        $this->busynessWeek2 = $user->busyness_week_2 ?? Busyness::LOW;
    }

    public function updateBusyness(): void
    {
        auth()->user()->update([
            'busyness_week_1' => $this->busynessWeek1,
            'busyness_week_2' => $this->busynessWeek2,
        ]);
    }

    public function updatedBusynessWeek1($value): void
    {
        $this->busynessWeek1 = $value instanceof Busyness ? $value : Busyness::from((int) $value);
        $this->updateBusyness();
    }

    public function updatedBusynessWeek2($value): void
    {
        $this->busynessWeek2 = $value instanceof Busyness ? $value : Busyness::from((int) $value);
        $this->updateBusyness();
    }

    public function calculateWeekRanges(): void
    {
        $today = now();

        // Get the start of the current week (Monday)
        $currentWeekStart = $today->startOfWeek();
        $currentWeekEnd = $currentWeekStart->copy()->addDays(4); // Friday

        // Get next week (Monday to Friday)
        $nextWeekStart = $currentWeekStart->copy()->addWeek();
        $nextWeekEnd = $nextWeekStart->copy()->addDays(4); // Friday

        $this->week1Start = $currentWeekStart->format('M j');
        $this->week1End = $currentWeekEnd->format('M j');
        $this->week2Start = $nextWeekStart->format('M j');
        $this->week2End = $nextWeekEnd->format('M j');
    }
}
