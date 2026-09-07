<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

enum ImportStatus: string
{
    case SUCCESS = 'success';
    case PARTIAL = 'partial';
    case FAILED = 'failed';

    public static function fromCounts(int $totalRecords, int $successfulRecords, int $failedRecords): self
    {
        if ($totalRecords === 0 || $successfulRecords === 0) {
            return self::FAILED;
        }

        if ($failedRecords === 0) {
            return self::SUCCESS;
        }

        return self::PARTIAL;
    }
}
