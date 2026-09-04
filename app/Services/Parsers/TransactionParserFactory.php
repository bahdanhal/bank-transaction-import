<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;
use InvalidArgumentException;

class TransactionParserFactory
{
    public function make(string $extension): TransactionParserInterface
    {
        return match (strtolower($extension)) {
            'csv' => app(CsvTransactionParser::class),
            'json' => app(JsonTransactionParser::class),
            'xml' => app(XmlTransactionParser::class),
            default => throw new InvalidArgumentException("Nieobsługiwany format pliku: {$extension}"),
        };
    }
}
