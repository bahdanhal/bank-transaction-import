<?php

declare(strict_types=1);

namespace Tests\Domain\Transaction\ValueObjects;

use App\Domain\Transaction\ValueObjects\Amount;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AmountTest extends TestCase
{
    public function test_can_create_valid_amount(): void
    {
        $amount = new Amount('150.50');

        $this->assertSame('150.50', $amount->value);
    }

    public function test_throws_exception_on_zero_or_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount should be more than zero');

        new Amount('-50.00');
    }

    public function test_throws_exception_on_non_numeric_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount field must be numeric.');

        new Amount('abc');
    }

    public function test_can_convert_to_money_object_and_perform_arithmetic(): void
    {
        $firstAmount = new Amount('150.50');
        $secondAmount = new Amount('49.50');

        $firstMoney = $firstAmount->toMoney('PLN');
        $secondMoney = $secondAmount->toMoney('PLN');

        $this->assertSame('15050', $firstMoney->getAmount());
        $this->assertSame('PLN', $firstMoney->getCurrency()->getCode());

        $sumMoney = $firstMoney->add($secondMoney);
        $this->assertSame('20000', $sumMoney->getAmount());
        $this->assertTrue($sumMoney->greaterThan($firstMoney));
    }
}
