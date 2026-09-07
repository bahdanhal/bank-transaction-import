<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Transaction\Parsers;

use App\Infrastructure\Transaction\Parsers\CsvTransactionParser;
use Generator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class CsvTransactionParserTest extends TestCase
{
    public function test_can_parse_csv_file_and_yield_trimmed_records(): void
    {
        $csvContent = implode("\n", [
            'transaction_id,account_number,transaction_date,amount,currency',
            ' 550e8400-e29b-41d4-a716-446655440000 , PL61109010140000071219812874 , 2025-10-14 , 150.00 , PLN ',
            ' 660e8400-e29b-41d4-a716-446655440000 , PL61109010140000071219812874 , 2025-10-15 , 200.00 , USD ',
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $parser = new CsvTransactionParser();
        $generator = $parser->parse($uploadedFile);

        $this->assertInstanceOf(Generator::class, $generator);

        $parsedRecords = iterator_to_array($generator, false);
        $this->assertCount(2, $parsedRecords);

        $this->assertSame([
            'transaction_id'   => '550e8400-e29b-41d4-a716-446655440000',
            'account_number'   => 'PL61109010140000071219812874',
            'transaction_date' => '2025-10-14',
            'amount'           => '150.00',
            'currency'         => 'PLN',
        ], $parsedRecords[0]);
    }
}
