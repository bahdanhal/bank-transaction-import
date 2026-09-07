<?php

declare(strict_types=1);

namespace Tests\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\Currency;
use InvalidArgumentException;
use Money\Currency as MoneyCurrency;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public function test_can_create_valid_currency(): void
    {
        $currency = new Currency('pln');

        $this->assertSame('PLN', $currency->value);
    }

    public function test_throws_exception_on_invalid_length_or_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency code must be 3 letters');

        new Currency('POLAND');
    }

    public function test_can_convert_to_moneyphp_currency(): void
    {
        $currency = new Currency('USD');
        $moneyCurrency = $currency->toMoneyCurrency();

        $this->assertInstanceOf(MoneyCurrency::class, $moneyCurrency);
        $this->assertSame('USD', $moneyCurrency->getCode());
    }
}
