<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Transaction\Parsers;

use App\Domain\Transaction\Exceptions\FileParsingException;
use App\Infrastructure\Transaction\Parsers\JsonTransactionParser;
use Generator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class JsonTransactionParserTest extends TestCase
{
    public function test_can_parse_flat_json_array(): void
    {
        $jsonContent = json_encode([
            [
                'transaction_id'   => '550e8400-e29b-41d4-a716-446655440000',
                'account_number'   => 'PL61109010140000071219812874',
                'transaction_date' => '2025-10-14',
                'amount'           => '150.00',
                'currency'         => 'PLN',
            ],
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('flat.json', (string) $jsonContent);

        $parser = new JsonTransactionParser();
        $generator = $parser->parse($uploadedFile);

        $this->assertInstanceOf(Generator::class, $generator);
        $parsedRecords = iterator_to_array($generator, false);
        $this->assertCount(1, $parsedRecords);
        $this->assertIsArray($parsedRecords[0]);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $parsedRecords[0]['transaction_id']);
    }

    public function test_can_parse_wrapped_json_object(): void
    {
        $jsonContent = json_encode([
            'transactions' => [
                [
                    'transaction_id'   => '550e8400-e29b-41d4-a716-446655440000',
                    'account_number'   => 'PL61109010140000071219812874',
                    'transaction_date' => '2025-10-14',
                    'amount'           => '150.00',
                    'currency'         => 'PLN',
                ],
            ],
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('wrapped.json', (string) $jsonContent);

        $parser = new JsonTransactionParser();
        $generator = $parser->parse($uploadedFile);

        $parsedRecords = iterator_to_array($generator, false);
        $this->assertCount(1, $parsedRecords);
        $this->assertIsArray($parsedRecords[0]);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $parsedRecords[0]['transaction_id']);
    }

    public function test_throws_file_parsing_exception_on_invalid_json(): void
    {
        $uploadedFile = UploadedFile::fake()->createWithContent('broken.json', '{ invalid json syntax');

        $parser = new JsonTransactionParser();
        $generator = $parser->parse($uploadedFile);

        $this->expectException(FileParsingException::class);
        $this->expectExceptionMessage('Invalid JSON syntax');

        // Generator execution starts upon iteration
        iterator_to_array($generator);
    }
}
