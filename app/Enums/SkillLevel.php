<?php

namespace App\Enums;

enum SkillLevel: string
{
    case NO_KNOWLEDGE = 'no_knowledge';
    case AWARENESS = 'awareness';
    case WORKING = 'working';
    case PRACTITIONER = 'practitioner';
    case EXPERT = 'expert';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::NO_KNOWLEDGE => 'No Knowledge',
            self::AWARENESS => 'Awareness',
            self::WORKING => 'Working',
            self::PRACTITIONER => 'Practitioner',
            self::EXPERT => 'Expert',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NO_KNOWLEDGE => 'No awareness, exposure or understanding of this skill',
            self::AWARENESS => 'You have an awareness/understanding of the skill but haven\'t applied it',
            self::WORKING => 'You can use the skill in a limited capacity with guidance',
            self::PRACTITIONER => 'You have a good level of expertise and can apply skills effectively',
            self::EXPERT => 'You are highly proficient and can teach or mentor others in this skill',
        };
    }

    public function getNumericValue(): int
    {
        return match ($this) {
            self::NO_KNOWLEDGE => 0,
            self::AWARENESS => 1,
            self::WORKING => 2,
            self::PRACTITIONER => 3,
            self::EXPERT => 4,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NO_KNOWLEDGE => 'zinc',
            self::AWARENESS => 'blue',
            self::WORKING => 'green',
            self::PRACTITIONER => 'amber',
            self::EXPERT => 'orange',
        };
    }

    /**
     * Get all storable skill level values (excludes NO_KNOWLEDGE).
     */
    public static function getAll(): array
    {
        return array_column(
            array_filter(self::cases(), fn ($case) => $case !== self::NO_KNOWLEDGE),
            'value'
        );
    }

    public static function getAllDisplayNames(): array
    {
        return array_map(fn ($case) => $case->getDisplayName(), self::cases());
    }
}
