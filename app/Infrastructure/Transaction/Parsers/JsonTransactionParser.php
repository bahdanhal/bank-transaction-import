<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Parsers;

use App\Application\Transaction\Contracts\TransactionParserInterface;
use App\Domain\Transaction\Exceptions\FileParsingException;
use Generator;
use Illuminate\Http\UploadedFile;
use JsonException;

final readonly class JsonTransactionParser implements TransactionParserInterface
{
    /**
     * @return Generator<int, mixed>
     */
    public function parse(UploadedFile $file): Generator
    {
        $data = $this->decode($file->getContent());

        if (!is_array($data)) {
            return;
        }

        // Extract nested collection if wrapped, otherwise use root
        $records = $data['transactions'] ?? $data['data'] ?? $data;

        if (!is_array($records)) {
            return;
        }

        // Ensure we always return a list of transactions
        $transactionList = array_is_list($records) ? $records : [$records];
        foreach ($transactionList as $record) {
            yield $record;
        }
    }

    private function decode(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new FileParsingException("Invalid JSON syntax: {$jsonException->getMessage()}", 0, $jsonException);
        }
    }
}
