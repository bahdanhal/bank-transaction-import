<?php

declare(strict_types=1);

namespace Tests\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\TransactionDate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransactionDateTest extends TestCase
{
    public function test_can_create_valid_transaction_date(): void
    {
        $transactionDate = new TransactionDate('2025-10-14');

        $this->assertSame('2025-10-14', $transactionDate->value);
    }

    public function test_normalizes_and_formats_date(): void
    {
        $transactionDate = new TransactionDate('  2025/10/14  ');

        $this->assertSame('2025-10-14', $transactionDate->value);
    }

    public function test_throws_exception_on_empty_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The transaction date field is required.');

        new TransactionDate('   ');
    }

    public function test_throws_exception_on_invalid_date_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The transaction date field must be a valid date.');

        new TransactionDate('not-a-real-date');
    }
}
