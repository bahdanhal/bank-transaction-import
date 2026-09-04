<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;
use Illuminate\Http\UploadedFile;
use JsonException;

class JsonTransactionParser implements TransactionParserInterface
{
    public function parse(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }
}
