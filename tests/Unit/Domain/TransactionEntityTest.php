<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Transaction\Entities\Transaction;
use App\Domain\Transaction\Exceptions\InvalidTransactionException;
use Tests\TestCase;

final class TransactionEntityTest extends TestCase
{
    private string $validIban = 'PL61109010140000071219812874';

    public function test_can_create_valid_transaction_from_raw(): void
    {
        $transaction = Transaction::createFromRaw([
            'transaction_id'   => '550e8400-e29b-41d4-a716-446655440000',
            'account_number'   => $this->validIban,
            'transaction_date' => '2025-10-14',
            'amount'           => '150.00',
            'currency'         => 'PLN',
        ]);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $transaction->transactionId->value);
        $this->assertSame($this->validIban, $transaction->accountNumber->value);
        $this->assertSame('2025-10-14', $transaction->transactionDate->value);
        $this->assertSame('150.00', $transaction->amount->value);
        $this->assertSame('PLN', $transaction->currency->value);
        $this->assertNull($transaction->id);

        $this->assertSame('150.00 PLN', $transaction->formattedAmount);

        $transactionWithDatabaseId = $transaction->withId(99);
        $this->assertSame(99, $transactionWithDatabaseId->id);
        $this->assertNull($transaction->id);
    }

    public function test_throws_invalid_transaction_exception_with_all_errors(): void
    {
        try {
            Transaction::createFromRaw([
                'transaction_id'   => 'not-a-uuid',
                'account_number'   => 'invalid-iban',
                'transaction_date' => 'invalid-date',
                'amount'           => '-10',
                'currency'         => 'TOOLONG',
            ]);
            $this->fail('Expected InvalidTransactionException was not thrown');
        } catch (InvalidTransactionException $invalidTransactionException) {
            $this->assertSame('not-a-uuid', $invalidTransactionException->transactionId);
            $this->assertCount(5, $invalidTransactionException->errors);
            $this->assertSame(5, $invalidTransactionException->errorCount);
            $this->assertStringContainsString('valid UUID', $invalidTransactionException->getMessage());
            $this->assertStringContainsString('IBAN', $invalidTransactionException->getMessage());
        }
    }
}
