<?php

declare(strict_types=1);

namespace App\Application\Transaction\Contracts;

use Illuminate\Http\UploadedFile;

interface TransactionParserInterface
{
    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function parse(UploadedFile $file): iterable;
}
