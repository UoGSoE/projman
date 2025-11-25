<?php

namespace App\Enums;

enum Priority: string
{
    case PRIORITY_1 = 'priority_1';
    case PRIORITY_2 = 'priority_2';
    case PRIORITY_3 = 'priority_3';
    case PRIORITY_4 = 'priority_4';
    case PRIORITY_5 = 'priority_5';

    public function label(): string
    {
        return match ($this) {
            self::PRIORITY_1 => 'Priority 1',
            self::PRIORITY_2 => 'Priority 2',
            self::PRIORITY_3 => 'Priority 3',
            self::PRIORITY_4 => 'Priority 4',
            self::PRIORITY_5 => 'Priority 5',
        };
    }
}
