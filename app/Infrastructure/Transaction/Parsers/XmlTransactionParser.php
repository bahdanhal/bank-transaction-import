<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Parsers;

use App\Application\Transaction\Contracts\TransactionParserInterface;
use App\Domain\Transaction\Exceptions\FileParsingException;
use Generator;
use Illuminate\Http\UploadedFile;

final readonly class XmlTransactionParser implements TransactionParserInterface
{
    /**
     * @return Generator<int, mixed>
     */
    public function parse(UploadedFile $file): Generator
    {
        $content = file_get_contents($file->getRealPath());
        $simpleXmlElement = @simplexml_load_string((string) $content, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($simpleXmlElement === false) {
            throw new FileParsingException('Invalid XML structure.');
        }

        $encodedJson = json_encode($simpleXmlElement);
        if ($encodedJson === false) {
            throw new FileParsingException('Invalid XML structure.');
        }

        $data = json_decode($encodedJson, true);
        if (!is_array($data) || !isset($data['transaction'])) {
            return;
        }

        $transactions = $data['transaction'];
        if (!is_array($transactions)) {
            return;
        }

        $transactionList = is_array(array_first($transactions)) ? $transactions : [$transactions];

        foreach ($transactionList as $transaction) {
            yield $transaction;
        }
    }
}
