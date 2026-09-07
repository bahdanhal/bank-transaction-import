<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

use Illuminate\Support\Facades\Validator;
use Intervention\Validation\Rules\Iban;
use InvalidArgumentException;

final readonly class AccountNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $normalizedAccountNumber = strtoupper(str_replace(' ', '', trim($value)));

        if ($normalizedAccountNumber === '') {
            throw new InvalidArgumentException('Account number is required');
        }

        $validator = Validator::make(
            ['account_number' => $normalizedAccountNumber],
            ['account_number' => ['required', 'string', new Iban()]],
            ['account_number.iban' => 'The account number must be a valid International Bank Account Number (IBAN).']
        );

        if ($validator->fails()) {
            $validationErrorMessage = $validator->errors()->first('account_number')
                ?: 'The account number must be a valid International Bank Account Number (IBAN).';
            throw new InvalidArgumentException($validationErrorMessage);
        }

        $this->value = $normalizedAccountNumber;
    }
}
