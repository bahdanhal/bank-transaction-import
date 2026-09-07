<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Transaction\ValueObjects\Currency;
use InvalidArgumentException;
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
}
