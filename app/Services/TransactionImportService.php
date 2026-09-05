<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Import;
use App\Models\Transaction;
use App\Services\Parsers\TransactionParserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Validation\Rules\Iban;
use Throwable;

final readonly class TransactionImportService
{
    public function __construct(
        private TransactionParserFactory $parserFactory
    ) {
    }

    public function handle(UploadedFile $file): Import
    {
        $import = Import::create([
            'file_name'          => $file->getClientOriginalName(),
            'total_records'      => 0,
            'successful_records' => 0,
            'failed_records'     => 1,
            'status'             => 'failed',
        ]);

        try {
            $records = $this->parserFactory
                ->make($file->getClientOriginalExtension())
                ->parse($file);

            if (empty($records)) {
                throw new \RuntimeException('No valid transaction records found in file.');
            }
        } catch (Throwable $e) {
            $import->logs()->create(['error_message' => $e->getMessage()]);
            return $import;
        }

        return DB::transaction(function () use ($import, $records) {
            $rules = [
                'account_number'   => ['required', 'string', new Iban()],
                'amount'           => ['required', 'numeric', 'gt:0'],
                'currency'         => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
                'transaction_id'   => ['required', 'uuid'],
                'transaction_date' => ['required', 'date'],
            ];

            $success = 0;

            foreach ($records as $record) {
                $validator = Validator::make((array) $record, $rules);

                if ($validator->fails()) {
                    $import->logs()->create([
                        'transaction_id' => is_string($record['transaction_id'] ?? null) ? $record['transaction_id'] : null,
                        'error_message'  => implode('; ', $validator->errors()->all()),
                    ]);
                    continue;
                }

                Transaction::create([
                    'transaction_id'   => $record['transaction_id'],
                    'account_number'   => strtoupper(str_replace(' ', '', (string) $record['account_number'])),
                    'transaction_date' => $record['transaction_date'],
                    'amount'           => $record['amount'],
                    'currency'         => strtoupper((string) $record['currency']),
                ]);

                $success++;
            }

            $total = count($records);
            $failed = $total - $success;

            $import->update([
                'total_records'      => $total,
                'successful_records' => $success,
                'failed_records'     => $failed,
                'status'             => $failed === 0 ? 'success' : ($success > 0 ? 'partial' : 'failed'),
            ]);

            return $import;
        });
    }
}
