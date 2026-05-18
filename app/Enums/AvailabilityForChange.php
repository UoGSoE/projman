<?php

namespace App\Enums;

enum AvailabilityForChange: int
{
    case None = 0;
    case Minimal = 20;
    case Low = 40;
    case Moderate = 60;
    case Good = 80;
    case Full = 100;

    public function label(): string
    {
        return $this->value.'%';
    }
}
