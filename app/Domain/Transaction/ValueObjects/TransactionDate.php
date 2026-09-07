<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final readonly class TransactionDate
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmedTransactionDate = trim($value);
        if ($trimmedTransactionDate === '') {
            throw new InvalidArgumentException('The transaction date field is required.');
        }

        try {
            $dateTime = new DateTimeImmutable($trimmedTransactionDate);
            $this->value = $dateTime->format('Y-m-d');
        } catch (Throwable) {
            throw new InvalidArgumentException('The transaction date field must be a valid date.');
        }
    }
}
