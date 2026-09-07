<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

use InvalidArgumentException;
use Money\Currency as MoneyCurrency;

final readonly class Currency
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmedCurrency = strtoupper(trim($value));

        if (!preg_match('/^[A-Z]{3}$/', $trimmedCurrency)) {
            throw new InvalidArgumentException('Currency code must be 3 letters');
        }

        $this->value = $trimmedCurrency;
    }

    public function toMoneyCurrency(): MoneyCurrency
    {
        return new MoneyCurrency($this->value);
    }
}
