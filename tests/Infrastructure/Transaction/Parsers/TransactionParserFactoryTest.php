<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Transaction\Parsers;

use App\Domain\Transaction\Exceptions\UnsupportedFileFormatException;
use App\Infrastructure\Transaction\Parsers\CsvTransactionParser;
use App\Infrastructure\Transaction\Parsers\JsonTransactionParser;
use App\Infrastructure\Transaction\Parsers\TransactionParserFactory;
use App\Infrastructure\Transaction\Parsers\XmlTransactionParser;
use Tests\TestCase;

final class TransactionParserFactoryTest extends TestCase
{
    public function test_creates_csv_parser_for_csv_extension(): void
    {
        $factory = new TransactionParserFactory();
        $parser = $factory->make('csv');

        $this->assertInstanceOf(CsvTransactionParser::class, $parser);
    }

    public function test_creates_json_parser_for_json_extension(): void
    {
        $factory = new TransactionParserFactory();
        $parser = $factory->make('json');

        $this->assertInstanceOf(JsonTransactionParser::class, $parser);
    }

    public function test_creates_xml_parser_for_xml_extension(): void
    {
        $factory = new TransactionParserFactory();
        $parser = $factory->make('xml');

        $this->assertInstanceOf(XmlTransactionParser::class, $parser);
    }

    public function test_throws_exception_for_unsupported_file_extension(): void
    {
        $factory = new TransactionParserFactory();

        $this->expectException(UnsupportedFileFormatException::class);
        $this->expectExceptionMessage('Unsupported file format: pdf');

        $factory->make('pdf');
    }
}
