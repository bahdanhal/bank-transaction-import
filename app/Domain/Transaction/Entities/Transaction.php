<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Entities;

use App\Domain\Transaction\Exceptions\InvalidTransactionException;
use App\Domain\Transaction\ValueObjects\AccountNumber;
use App\Domain\Transaction\ValueObjects\Amount;
use App\Domain\Transaction\ValueObjects\Currency;
use App\Domain\Transaction\ValueObjects\TransactionDate;
use App\Domain\Transaction\ValueObjects\TransactionId;
use InvalidArgumentException;
use NoDiscard;

final class Transaction
{
    public function __construct(
        private(set) TransactionId $transactionId,
        private(set) AccountNumber $accountNumber,
        private(set) TransactionDate $transactionDate,
        private(set) Amount $amount,
        private(set) Currency $currency,
        private(set) ?int $id = null
    ) {
    }

    public string $formattedAmount {
        get => "{$this->amount->value} {$this->currency->value}";
    }

    /**
     * @param array<string, mixed> $data
     * @throws InvalidTransactionException
     */
    public static function createFromRaw(array $data): self
    {
        $errors = [];
        $rawTransactionId = isset($data['transaction_id']) && is_string($data['transaction_id'])
            ? trim($data['transaction_id'])
            : null;

        // 1. Transaction ID
        $transactionId = null;
        if (!isset($data['transaction_id']) || trim((string) $data['transaction_id']) === '') {
            $errors[] = 'The transaction id field is required.';
        } else {
            try {
                $transactionId = new TransactionId((string) $data['transaction_id']);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 2. Account Number
        $accountNumber = null;
        if (!isset($data['account_number']) || trim((string) $data['account_number']) === '') {
            $errors[] = 'Account number is required';
        } else {
            try {
                $accountNumber = new AccountNumber((string) $data['account_number']);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 3. Transaction Date
        $transactionDate = null;
        if (!isset($data['transaction_date']) || trim((string) $data['transaction_date']) === '') {
            $errors[] = 'The transaction date field is required.';
        } else {
            try {
                $transactionDate = new TransactionDate((string) $data['transaction_date']);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 4. Amount
        $amount = null;
        if (!isset($data['amount']) || trim((string) $data['amount']) === '') {
            $errors[] = 'The amount field is required.';
        } else {
            try {
                $amount = new Amount($data['amount']);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 5. Currency
        $currency = null;
        if (!isset($data['currency']) || trim((string) $data['currency']) === '') {
            $errors[] = 'The currency field is required.';
        } else {
            try {
                $currency = new Currency((string) $data['currency']);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        if (!empty($errors) || $transactionId === null || $accountNumber === null || $transactionDate === null || $amount === null || $currency === null) {
            throw new InvalidTransactionException($errors, $rawTransactionId);
        }

        return new self($transactionId, $accountNumber, $transactionDate, $amount, $currency);
    }

    #[\NoDiscard]
    public function withId(int $id): self
    {
        return clone($this, ['id' => $id]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'transaction_id'   => $this->transactionId->value,
            'account_number'   => $this->accountNumber->value,
            'transaction_date' => $this->transactionDate->value,
            'amount'           => $this->amount->value,
            'currency'         => $this->currency->value,
        ];
    }
}
