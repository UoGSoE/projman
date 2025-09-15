<?php

namespace App\Enums;

enum SkillLevel: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';
    case EXPERT = 'expert';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
            self::EXPERT => 'Expert',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::BEGINNER => 'Basic understanding, can work with assistance',
            self::INTERMEDIATE => 'Good understanding, can work independently on most tasks',
            self::ADVANCED => 'Strong understanding, can handle complex tasks and mentor others',
            self::EXPERT => 'Mastery level, can lead and innovate in this area',
        };
    }

    public function getNumericValue(): int
    {
        return match ($this) {
            self::BEGINNER => 1,
            self::INTERMEDIATE => 2,
            self::ADVANCED => 3,
            self::EXPERT => 4,
        };
    }

    public static function getAll(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getAllDisplayNames(): array
    {
        return array_map(fn($case) => $case->getDisplayName(), self::cases());
    }
}
