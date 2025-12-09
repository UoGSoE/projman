<?php

namespace App\Enums;

use App\Models\Build;
use App\Models\Deployed;
use App\Models\DetailedDesign;
use App\Models\Development;
use App\Models\Feasibility;
use App\Models\Ideation;
use App\Models\Scheduling;
use App\Models\Scoping;
use App\Models\Testing;

enum ProjectStatus: string
{
    case IDEATION = 'ideation';
    case FEASIBILITY = 'feasibility';
    case SCOPING = 'scoping';
    case SCHEDULING = 'scheduling';
    case DETAILED_DESIGN = 'detailed-design';
    case DEVELOPMENT = 'development';
    case BUILD = 'build';
    case TESTING = 'testing';
    case DEPLOYED = 'deployed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::IDEATION => 'Ideation',
            self::FEASIBILITY => 'Feasibility',
            self::SCOPING => 'Scoping',
            self::SCHEDULING => 'Scheduling',
            self::DETAILED_DESIGN => 'Detailed Design',
            self::DEVELOPMENT => 'Development',
            self::TESTING => 'Testing',
            self::DEPLOYED => 'Deployed',
            self::BUILD => 'Build',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function colour(): string
    {
        return match ($this) {
            self::IDEATION => 'lime',
            self::FEASIBILITY => 'green',
            self::SCOPING => 'amber',
            self::SCHEDULING => 'amber',
            self::DETAILED_DESIGN => 'amber',
            self::DEVELOPMENT => 'amber',
            self::TESTING => 'amber',
            self::DEPLOYED => 'green',
            self::BUILD => 'amber',
            self::COMPLETED => 'zinc',
            self::CANCELLED => 'red',
        };
    }

    public function getFormName(): string
    {
        return match ($this) {
            self::IDEATION => 'ideationForm',
            self::FEASIBILITY => 'feasibilityForm',
            self::SCOPING => 'scopingForm',
            self::SCHEDULING => 'schedulingForm',
            self::DETAILED_DESIGN => 'detailedDesignForm',
            self::DEVELOPMENT => 'developmentForm',
            self::TESTING => 'testingForm',
            self::DEPLOYED => 'deployedForm',
            self::BUILD => 'buildForm',
        };
    }

    public function getNextStatus(): ?self
    {
        return match ($this) {
            self::IDEATION => self::FEASIBILITY,
            self::FEASIBILITY => self::SCOPING,
            self::SCOPING => self::SCHEDULING,
            self::SCHEDULING => self::DETAILED_DESIGN,
            self::DETAILED_DESIGN => self::DEVELOPMENT,
            self::DEVELOPMENT => self::BUILD,
            self::BUILD => self::TESTING,
            self::TESTING => self::DEPLOYED,
            self::DEPLOYED => self::COMPLETED,
        };
    }

    public static function getAll(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getAllFormNames(): array
    {
        return array_map(fn ($case) => $case->getFormName(), [self::IDEATION, self::FEASIBILITY, self::SCOPING, self::SCHEDULING, self::DETAILED_DESIGN, self::DEVELOPMENT, self::TESTING, self::DEPLOYED, self::BUILD]);
    }

    public function getStageColor(ProjectStatus $currentStage): string
    {
        // Handle special cases
        if ($this === self::CANCELLED || $currentStage === self::CANCELLED) {
            return 'red';
        }

        if ($currentStage === self::COMPLETED) {
            return $this === self::COMPLETED ? 'green' : 'green'; // all stages green when completed
        }

        $stageOrder = $this->getStageOrder();
        $currentOrder = $currentStage->getStageOrder();

        return match (true) {
            $stageOrder < $currentOrder => 'green',  // completed
            $stageOrder === $currentOrder => 'amber', // current
            default => 'zinc'  // not yet reached
        };
    }

    private function getStageOrder(): int
    {
        $progressStages = self::getProgressStages();
        $index = array_search($this, $progressStages, true);

        // Return the index + 1 (so we start at 1, not 0), or 999 for cancelled
        return $index !== false ? $index + 1 : 999;
    }

    public static function getProgressStages(): array
    {
        $allCases = self::cases();

        // Remove CANCELLED as it's not part of the normal progression
        return array_filter($allCases, fn ($case) => $case !== self::CANCELLED);
    }

    /**
     * @return array<self>
     */
    public static function stageStatuses(): array
    {
        return array_filter(self::cases(), fn (self $status) => $status->stageModel() !== null);
    }

    public function relationName(): ?string
    {
        return match ($this) {
            self::IDEATION => 'ideation',
            self::FEASIBILITY => 'feasibility',
            self::SCOPING => 'scoping',
            self::SCHEDULING => 'scheduling',
            self::DETAILED_DESIGN => 'detailedDesign',
            self::DEVELOPMENT => 'development',
            self::TESTING => 'testing',
            self::DEPLOYED => 'deployed',
            self::BUILD => 'build',
            default => null,
        };
    }

    public function stageModel(): ?string
    {
        return match ($this) {
            self::IDEATION => Ideation::class,
            self::FEASIBILITY => Feasibility::class,
            self::SCOPING => Scoping::class,
            self::SCHEDULING => Scheduling::class,
            self::DETAILED_DESIGN => DetailedDesign::class,
            self::DEVELOPMENT => Development::class,
            self::TESTING => Testing::class,
            self::DEPLOYED => Deployed::class,
            self::BUILD => Build::class,
            default => null,
        };
    }
}
