<?php

namespace App\Enums;

enum ServiceFunction: string
{
    case COLLEGE_INFRASTRUCTURE = 'college_infrastructure';
    case RESEARCH_COMPUTING = 'research_computing';
    case APPLICATIONS_DATA = 'applications_data';
    case SERVICE_RESILIENCE = 'service_resilience';
    case SERVICE_DELIVERY = 'service_delivery';

    public function label(): string
    {
        return match ($this) {
            self::COLLEGE_INFRASTRUCTURE => 'College Infrastructure',
            self::RESEARCH_COMPUTING => 'Research Computing',
            self::APPLICATIONS_DATA => 'Applications & Data',
            self::SERVICE_RESILIENCE => 'Service Resilience',
            self::SERVICE_DELIVERY => 'Service Delivery',
        };
    }
}
