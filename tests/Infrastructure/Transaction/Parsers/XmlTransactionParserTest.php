<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Transaction\Parsers;

use App\Domain\Transaction\Exceptions\FileParsingException;
use App\Infrastructure\Transaction\Parsers\XmlTransactionParser;
use Generator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class XmlTransactionParserTest extends TestCase
{
    public function test_can_parse_valid_xml(): void
    {
        $xmlContent = <<<XML
        <transactions>
          <transaction>
            <transaction_id>550e8400-e29b-41d4-a716-446655440000</transaction_id>
            <account_number>PL61109010140000071219812874</account_number>
            <transaction_date>2025-10-14</transaction_date>
            <amount>150.00</amount>
            <currency>PLN</currency>
          </transaction>
        </transactions>
        XML;

        $uploadedFile = UploadedFile::fake()->createWithContent('valid.xml', $xmlContent);

        $parser = new XmlTransactionParser();
        $generator = $parser->parse($uploadedFile);

        $this->assertInstanceOf(Generator::class, $generator);
        $parsedRecords = iterator_to_array($generator, false);
        $this->assertCount(1, $parsedRecords);
        $this->assertIsArray($parsedRecords[0]);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $parsedRecords[0]['transaction_id']);
    }

    public function test_throws_file_parsing_exception_on_invalid_xml(): void
    {
        $uploadedFile = UploadedFile::fake()->createWithContent('broken.xml', '<transactions><unclosed>');

        $parser = new XmlTransactionParser();
        $generator = $parser->parse($uploadedFile);

        $this->expectException(FileParsingException::class);
        $this->expectExceptionMessage('Invalid XML structure.');

        iterator_to_array($generator);
    }
}
