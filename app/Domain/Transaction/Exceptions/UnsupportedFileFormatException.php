<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Exceptions;

use DomainException;

final class UnsupportedFileFormatException extends DomainException
{
    public static function forExtension(string $extension): self
    {
        return new self("Unsupported file format: {$extension}");
    }
}
