<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case IDEATION = 'ideation';
    case FEASIBILITY = 'feasibility';
    case SCOPING = 'scoping';
    case SCHEDULING = 'scheduling';
    case DETAILED_DESIGN = 'detailed-design';
    case DEVELOPMENT = 'development';
    case TESTING = 'testing';
    case DEPLOYED = 'deployed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function colour(): string
    {
        return match ($this) {
            self::IDEATION => 'lime',
            self::FEASIBILITY => 'green',
            self::COMPLETED => 'zinc',
            self::CANCELLED => 'indigo',
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
            self::DEVELOPMENT => self::TESTING,
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
        return array_map(fn($case) => $case->getFormName(), self::cases());
    }
}
