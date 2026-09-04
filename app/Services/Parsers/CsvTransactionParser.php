<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;

class CsvTransactionParser implements TransactionParserInterface
{
    public function parse(UploadedFile $file): array
    {
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);
        $csv->skipEmptyRecords();

        $records = [];
        foreach ($csv->getRecords() as $record) {
            $records[] = array_map('trim', $record);
        }

        return $records;
    }
}
