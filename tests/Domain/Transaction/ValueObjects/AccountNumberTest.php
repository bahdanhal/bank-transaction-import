<?php

declare(strict_types=1);

namespace Tests\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\AccountNumber;
use InvalidArgumentException;
use Tests\TestCase;

final class AccountNumberTest extends TestCase
{
    private string $validIban = 'PL61109010140000071219812874';

    public function test_can_create_valid_account_number(): void
    {
        $accountNumber = new AccountNumber($this->validIban);

        $this->assertSame($this->validIban, $accountNumber->value);
    }

    public function test_normalizes_spaces_and_lowercase(): void
    {
        $rawAccountNumber = ' pl 61 1090 1014 0000 0712 1981 2874 ';
        $accountNumber = new AccountNumber($rawAccountNumber);

        $this->assertSame($this->validIban, $accountNumber->value);
    }

    public function test_throws_exception_on_invalid_iban_checksum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The account number must be a valid International Bank Account Number (IBAN).');

        new AccountNumber('PL00000000000000000000000000');
    }

    public function test_throws_exception_on_empty_account_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account number is required');

        new AccountNumber('   ');
    }
}
