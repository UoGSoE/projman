<?php

namespace App\Enums;

enum DevelopmentStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case CODE_REVIEW = 'code_review';
    case TESTING = 'testing';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Not Started',
            self::IN_PROGRESS => 'In Progress',
            self::CODE_REVIEW => 'Code Review',
            self::TESTING => 'Testing',
            self::COMPLETED => 'Completed',
        };
    }
}
