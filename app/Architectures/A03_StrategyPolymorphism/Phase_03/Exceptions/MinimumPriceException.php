<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_03\Exceptions;

class MinimumPriceException extends \Exception
{
    public function __construct(float $current, float $minimum)
    {
        parent::__construct(
            "The discounted price ({$current}) is below the minimum guaranteed price of {$minimum}."
        );
    }
}
