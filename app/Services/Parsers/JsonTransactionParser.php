<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;
use Illuminate\Http\UploadedFile;
use JsonException;
use RuntimeException;

final class JsonTransactionParser implements TransactionParserInterface
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
        } catch (JsonException) {
            throw new RuntimeException("Invalid JSON syntax: {$e->getMessage()}", 0, $e);
        }
    }
}
