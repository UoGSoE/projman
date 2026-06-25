<?php

namespace App\Enums;

enum AgbApproval: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case NOT_REQUIRED = 'not_required';

    public function label(): string
    {
        return match ($this) {
            self::NOT_REQUIRED => 'Not Required',
            default => ucfirst($this->value),
        };
    }
}
