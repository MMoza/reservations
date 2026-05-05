<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_04\Exceptions;

use Exception;

class MinimumPriceException extends Exception
{
    public function __construct(float $calculatedPrice, float $minimumPrice)
    {
        parent::__construct(
            "Price after discounts ({$calculatedPrice}) is below the minimum allowed ({$minimumPrice}).",
            422
        );
    }
}
