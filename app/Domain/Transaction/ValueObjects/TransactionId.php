<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class TransactionId
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmedTransactionId = trim($value);

        if (!Str::isUuid($trimmedTransactionId)) {
            throw new InvalidArgumentException('The transaction id field must be a valid UUID.');
        }

        $this->value = strtolower($trimmedTransactionId);
    }
}
