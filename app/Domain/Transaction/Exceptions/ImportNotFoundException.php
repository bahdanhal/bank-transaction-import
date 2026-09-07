<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Exceptions;

use DomainException;

final class ImportNotFoundException extends DomainException
{
    public static function forId(int $id): self
    {
        return new self("Import with ID {$id} not found.");
    }
}
