<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Transaction\Repositories;

use App\Domain\Transaction\Entities\Transaction;
use App\Infrastructure\Transaction\Repositories\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TransactionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private string $validIban = 'PL61109010140000071219812874';

    public function test_can_save_single_transaction(): void
    {
        $repository = new TransactionRepository();
        $transaction = Transaction::createFromRaw([
            'transaction_id'   => '550e8400-e29b-41d4-a716-446655440000',
            'account_number'   => $this->validIban,
            'transaction_date' => '2025-10-14',
            'amount'           => '150.00',
            'currency'         => 'PLN',
        ]);

        $savedTransaction = $repository->save($transaction);

        $this->assertNotNull($savedTransaction->id);
        $this->assertDatabaseHas('transactions', [
            'id'             => $savedTransaction->id,
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);
    }

    public function test_can_save_multiple_transactions_with_batching(): void
    {
        $repository = new TransactionRepository();
        $transactionBatch = [];

        for ($index = 1; $index <= 10; $index++) {
            $transactionBatch[] = Transaction::createFromRaw([
                'transaction_id'   => (string) Str::uuid(),
                'account_number'   => $this->validIban,
                'transaction_date' => '2025-10-14',
                'amount'           => '100.00',
                'currency'         => 'PLN',
            ]);
        }

        $savedTransactions = $repository->saveMany($transactionBatch);

        $this->assertCount(10, $savedTransactions);
        $this->assertDatabaseCount('transactions', 10);
    }

    public function test_save_many_with_empty_array_returns_empty_array(): void
    {
        $repository = new TransactionRepository();
        $savedTransactions = $repository->saveMany([]);

        $this->assertSame([], $savedTransactions);
        $this->assertDatabaseCount('transactions', 0);
    }
}
