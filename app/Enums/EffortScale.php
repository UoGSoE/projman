<?php

namespace App\Enums;

enum EffortScale: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case X_LARGE = 'xlarge';
    case XX_LARGE = 'xxlarge';

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Small (≤5 days)',
            self::MEDIUM => 'Medium (6-15 days)',
            self::LARGE => 'Large (30-50 days)',
            self::X_LARGE => 'X-Large (51-100 days)',
            self::XX_LARGE => 'XX-Large (>101 days)',
        };
    }

    public function daysRange(): string
    {
        return match ($this) {
            self::SMALL => '≤5',
            self::MEDIUM => '6-15',
            self::LARGE => '30-50',
            self::X_LARGE => '51-100',
            self::XX_LARGE => '>101',
        };
    }
}
