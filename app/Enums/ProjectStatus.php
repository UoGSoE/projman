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
}
