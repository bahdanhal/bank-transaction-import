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
use Money\Money;
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

    public Money $money {
        get => $this->amount->toMoney($this->currency);
    }

    /**
     * @param array<mixed, mixed> $data
     * @throws InvalidTransactionException
     */
    public static function createFromRaw(array $data): self
    {
        $errors = [];
        $rawTransactionIdValue = $data['transaction_id'] ?? null;
        $rawTransactionId = is_string($rawTransactionIdValue) ? trim($rawTransactionIdValue) : null;

        // 1. Transaction ID
        $transactionId = null;
        $transactionIdString = self::extractString($rawTransactionIdValue);
        if ($transactionIdString === '') {
            $errors[] = 'The transaction id field is required.';
        } else {
            try {
                $transactionId = new TransactionId($transactionIdString);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 2. Account Number
        $accountNumber = null;
        $accountNumberString = self::extractString($data['account_number'] ?? null);
        if ($accountNumberString === '') {
            $errors[] = 'Account number is required';
        } else {
            try {
                $accountNumber = new AccountNumber($accountNumberString);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 3. Transaction Date
        $transactionDate = null;
        $transactionDateString = self::extractString($data['transaction_date'] ?? null);
        if ($transactionDateString === '') {
            $errors[] = 'The transaction date field is required.';
        } else {
            try {
                $transactionDate = new TransactionDate($transactionDateString);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 4. Amount
        $amount = null;
        $rawAmount = $data['amount'] ?? null;
        if (!is_scalar($rawAmount) || trim((string) $rawAmount) === '') {
            $errors[] = 'The amount field is required.';
        } else {
            try {
                $amountValue = is_string($rawAmount) || is_int($rawAmount) || is_float($rawAmount)
                    ? $rawAmount
                    : (string) $rawAmount;
                $amount = new Amount($amountValue);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        // 5. Currency
        $currency = null;
        $currencyString = self::extractString($data['currency'] ?? null);
        if ($currencyString === '') {
            $errors[] = 'The currency field is required.';
        } else {
            try {
                $currency = new Currency($currencyString);
            } catch (InvalidArgumentException $validationException) {
                $errors[] = $validationException->getMessage();
            }
        }

        $hasMissingProperties = $transactionId === null
            || $accountNumber === null
            || $transactionDate === null
            || $amount === null
            || $currency === null;

        if (!empty($errors) || $hasMissingProperties) {
            throw new InvalidTransactionException($errors, $rawTransactionId);
        }

        return new self($transactionId, $accountNumber, $transactionDate, $amount, $currency);
    }

    private static function extractString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
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
