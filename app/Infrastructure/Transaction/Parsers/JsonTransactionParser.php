<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Parsers;

use App\Application\Transaction\Contracts\TransactionParserInterface;
use App\Domain\Transaction\Exceptions\FileParsingException;
use Illuminate\Http\UploadedFile;
use JsonException;

final readonly class JsonTransactionParser implements TransactionParserInterface
{
    public function parse(UploadedFile $file): array
    {
        $data = $this->decode($file->getContent());

        if (!is_array($data)) {
            return [];
        }

        // Extract nested collection if wrapped, otherwise use root
        $records = $data['transactions'] ?? $data['data'] ?? $data;

        if (!is_array($records)) {
            return [];
        }

        // Ensure we always return a list of transactions
        return array_is_list($records) ? $records : [$records];
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
