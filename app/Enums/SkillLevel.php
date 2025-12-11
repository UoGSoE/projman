<?php

namespace App\Enums;

enum SkillLevel: string
{
    /**
     * No Knowledge	- No awareness, exposure or understanding of this skill
     * Awareness	- You have an awareness/understanding of the skill  but haven't applied it..
     * Working	    - You can use the skill in a limited capacity with guidance.
     * Practitioner	- You have a good level of expertise and can apply skills effectively
     * Expert	    - You are highly proficient and can teach or mentor others in this skill.
    */
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::BEGINNER => 'Basic understanding, can work with assistance',
            self::INTERMEDIATE => 'Good understanding, can work independently on most tasks',
            self::ADVANCED => 'Strong understanding, can handle complex tasks and mentor others',
        };
    }

    public function getNumericValue(): int
    {
        return match ($this) {
            self::BEGINNER => 1,
            self::INTERMEDIATE => 2,
            self::ADVANCED => 3,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BEGINNER => 'blue',
            self::INTERMEDIATE => 'green',
            self::ADVANCED => 'orange',
        };
    }

    public static function getAll(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getAllDisplayNames(): array
    {
        return array_map(fn ($case) => $case->getDisplayName(), self::cases());
    }
}
