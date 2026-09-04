<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use App\Contracts\TransactionParserInterface;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class XmlTransactionParser implements TransactionParserInterface
{
    public function parse(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            throw new RuntimeException("Niepoprawna struktura XML.");
        }

        $data = json_decode(json_encode($xml), true);
        if (!isset($data['transaction'])) {
            return [];
        }

        // Obsługa pojedynczego lub wielu elementów <transaction>
        return isset($data['transaction'][0]) ? $data['transaction'] : [$data['transaction']];
    }
}
