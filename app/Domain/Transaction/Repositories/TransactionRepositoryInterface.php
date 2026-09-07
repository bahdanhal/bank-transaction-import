<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Entities\Transaction;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): Transaction;

    /**
     * @param array<int, Transaction> $transactions
     * @return array<int, Transaction>
     */
    public function saveMany(array $transactions): array;
}
