<?php

namespace App\Enums;

enum EffortScale: string
{
    case SMALL = 'small';          // ≤5 days
    case MEDIUM = 'medium';        // >5 ≤15 days
    case LARGE = 'large';          // >15 ≤30 days
    case X_LARGE = 'xlarge';       // >30 ≤50 days
    case XX_LARGE = 'xxlarge';     // >50 days

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Small (≤5 days)',
            self::MEDIUM => 'Medium (6-15 days)',
            self::LARGE => 'Large (16-30 days)',
            self::X_LARGE => 'X-Large (31-50 days)',
            self::XX_LARGE => 'XX-Large (>50 days)',
        };
    }

    public function daysRange(): string
    {
        return match ($this) {
            self::SMALL => '≤5',
            self::MEDIUM => '6-15',
            self::LARGE => '16-30',
            self::X_LARGE => '31-50',
            self::XX_LARGE => '>50',
        };
    }
}
