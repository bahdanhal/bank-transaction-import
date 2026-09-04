<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface TransactionParserInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(UploadedFile $file): array;
}
