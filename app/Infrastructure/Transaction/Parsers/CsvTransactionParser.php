<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Parsers;

use App\Application\Transaction\Contracts\TransactionParserInterface;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;

final readonly class CsvTransactionParser implements TransactionParserInterface
{
    public function parse(UploadedFile $file): array
    {
        $csvReader = Reader::createFromPath($file->getRealPath(), 'r');
        $csvReader->setHeaderOffset(0);
        $csvReader->skipEmptyRecords();

        $records = [];
        foreach ($csvReader->getRecords() as $record) {
            $records[] = array_map('trim', $record);
        }

        return $records;
    }
}
