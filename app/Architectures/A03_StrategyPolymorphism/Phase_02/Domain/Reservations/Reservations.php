<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Reservations;

abstract class Reservations
{
    protected array $lines = [];

    abstract public function calculateTotal(): float;

    public function addLine(float $amount): void
    {
        $this->lines[] = $amount;
    }

    protected function sumLines(): float
    {
        return array_sum($this->lines);
    }
}
