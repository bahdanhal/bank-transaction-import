<?php

declare(strict_types=1);

namespace App\Domain\Transaction\ValueObjects;

use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Money\Currency as MoneyCurrency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

final readonly class Amount
{
    public string $value;

    public function __construct(int|float|string $value)
    {
        if (is_string($value) && trim($value) === '') {
            throw new InvalidArgumentException('The amount field is required.');
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException('The amount field must be numeric.');
        }

        $numericAmount = (float) $value;
        if ($numericAmount <= 0) {
            throw new InvalidArgumentException('Amount should be more than zero');
        }

        $formattedDecimal = number_format($numericAmount, 2, '.', '');
        $isoCurrencies = new ISOCurrencies();
        $decimalParser = new DecimalMoneyParser($isoCurrencies);
        $decimalFormatter = new DecimalMoneyFormatter($isoCurrencies);

        $parsedMoney = $decimalParser->parse($formattedDecimal, new MoneyCurrency('EUR'));
        if (!$parsedMoney->isPositive()) {
            throw new InvalidArgumentException('Amount should be more than zero');
        }

        $this->value = $decimalFormatter->format($parsedMoney);
    }

    public function toMoney(Currency|string $currency): Money
    {
        $moneyCurrency = $currency instanceof Currency
            ? $currency->toMoneyCurrency()
            : (new Currency($currency))->toMoneyCurrency();
        $isoCurrencies = new ISOCurrencies();
        $decimalParser = new DecimalMoneyParser($isoCurrencies);

        return $decimalParser->parse($this->value, $moneyCurrency);
    }
}
