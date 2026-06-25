<?php

namespace App\Support;

class HeatmapCell
{
    public function __construct(public float $utilisation) {}

    public function colour(): string
    {
        return match (true) {
            $this->utilisation > 1.0 => 'bg-black',
            $this->utilisation >= 0.9 => 'bg-red-500',
            $this->utilisation >= 0.7 => 'bg-amber-500',
            default => 'bg-green-500',
        };
    }

    public function label(): string
    {
        return (int) round($this->utilisation * 100).'%';
    }
}
