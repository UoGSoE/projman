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

    /**
     * Calculate busyness level from active project count.
     *
     * Thresholds: 0=UNKNOWN, 1-2=LOW, 3-4=MEDIUM, 5+=HIGH
     */
    public static function fromProjectCount(int $count): self
    {
        return match (true) {
            $count >= 5 => self::HIGH,
            $count >= 3 => self::MEDIUM,
            $count >= 1 => self::LOW,
            default => self::UNKNOWN,
        };
    }
}
