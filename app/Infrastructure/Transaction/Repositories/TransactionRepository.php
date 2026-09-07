<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Repositories;

use App\Domain\Transaction\Entities\Transaction as DomainTransaction;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Models\Transaction as TransactionModel;

final readonly class TransactionRepository implements TransactionRepositoryInterface
{
    public function save(DomainTransaction $transaction): DomainTransaction
    {
        $transactionModel = TransactionModel::create([
            'transaction_id'   => $transaction->transactionId->value,
            'account_number'   => $transaction->accountNumber->value,
            'transaction_date' => $transaction->transactionDate->value,
            'amount'           => $transaction->amount->value,
            'currency'         => $transaction->currency->value,
        ]);

        return $transaction->withId($transactionModel->id);
    }

    /**
     * @param array<int, DomainTransaction> $transactions
     * @return array<int, DomainTransaction>
     */
    public function saveMany(array $transactions): array
    {
        if (empty($transactions)) {
            return [];
        }

        $recordsToInsert = [];
        $currentTimestamp = now();
        foreach ($transactions as $transaction) {
            $recordsToInsert[] = [
                'transaction_id'   => $transaction->transactionId->value,
                'account_number'   => $transaction->accountNumber->value,
                'transaction_date' => $transaction->transactionDate->value,
                'amount'           => $transaction->amount->value,
                'currency'         => $transaction->currency->value,
                'created_at'       => $currentTimestamp,
                'updated_at'       => $currentTimestamp,
            ];
        }

        foreach (array_chunk($recordsToInsert, 500) as $chunkOfTransactions) {
            TransactionModel::insert($chunkOfTransactions);
        }

        return $transactions;
    }
}
