<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Exceptions;

use DomainException;

final class InvalidTransactionException extends DomainException
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(
        private(set) array $errors,
        private(set) ?string $transactionId = null
    ) {
        $message = implode('; ', $this->errors);
        parent::__construct($message);
    }

    public int $errorCount {
        get => count($this->errors);
    }
}
