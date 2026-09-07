<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

use InvalidArgumentException;

final readonly class Amount
{
    public string $value;

    public function __construct(int|float|string $value)
    {
        if (is_string($value) && trim($value) === '') {
            throw new InvalidArgumentException('The amount field is required.');
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('The amount field must be numeric.');
        }

        $numericAmount = (float) $value;
        if ($numericAmount <= 0) {
            throw new InvalidArgumentException('Amount should be more than zero');
        }

        $this->value = number_format($numericAmount, 2, '.', '');
    }
}
