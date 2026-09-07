<?php

declare(strict_types=1);

namespace Tests\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\TransactionId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TransactionIdTest extends TestCase
{
    public function test_can_create_valid_uuid(): void
    {
        $uuidString = '550e8400-e29b-41d4-a716-446655440000';
        $transactionId = new TransactionId($uuidString);

        $this->assertSame($uuidString, $transactionId->value);
    }

    public function test_throws_exception_on_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The transaction id field must be a valid UUID.');

        new TransactionId('not-a-valid-uuid');
    }
}
