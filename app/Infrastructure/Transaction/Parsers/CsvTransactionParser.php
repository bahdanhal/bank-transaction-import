<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Parsers;

use App\Application\Transaction\Contracts\TransactionParserInterface;
use Generator;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;

final readonly class CsvTransactionParser implements TransactionParserInterface
{
    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function parse(UploadedFile $file): Generator
    {
        $csvReader = Reader::createFromPath($file->getRealPath(), 'r');
        $csvReader->setHeaderOffset(0);
        $csvReader->skipEmptyRecords();

        foreach ($csvReader->getRecords() as $record) {
            $trimmedRecord = [];
            foreach ($record as $key => $field) {
                $trimmedRecord[(string) $key] = is_scalar($field) ? trim((string) $field) : '';
            }
            yield $trimmedRecord;
        }
    }
}
