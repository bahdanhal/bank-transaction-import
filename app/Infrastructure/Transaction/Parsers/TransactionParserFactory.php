<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Parsers;

use App\Application\Transaction\Contracts\TransactionParserInterface;
use App\Domain\Transaction\Exceptions\UnsupportedFileFormatException;

final readonly class TransactionParserFactory
{
    public function make(string $extension): TransactionParserInterface
    {
        return match (strtolower($extension)) {
            'csv' => app(CsvTransactionParser::class),
            'json' => app(JsonTransactionParser::class),
            'xml' => app(XmlTransactionParser::class),
            default => throw UnsupportedFileFormatException::forExtension($extension),
        };
    }
}
