<?php

namespace App\Enums;

enum ChangeBoardOutcome: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DEFERRED = 'deferred';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
