<?php

namespace App\Enums;

enum Busyness: int
{
    case UNKNOWN = 0;
    case LOW = 30;
    case MEDIUM = 60;
    case HIGH = 90;

    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Unknown',
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
        };
    }

    public function color(): string
    {
        return match (true) {
            $this === self::UNKNOWN => 'bg-gray-600',
            $this->value < 60 => 'bg-green-500',
            $this->value < 90 => 'bg-yellow-500',
            default => 'bg-red-500',
        };
    }
}
