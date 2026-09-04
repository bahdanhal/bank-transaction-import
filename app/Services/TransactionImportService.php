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

final class TransactionImportService
{
    public function __construct(
        private TransactionParserFactory $parserFactory
    ) {}

    public function handle(UploadedFile $file): Import
    {
        $extension = $file->getClientOriginalExtension();
        $parser = $this->parserFactory->make($extension);
        $records = $parser->parse($file);

        return DB::transaction(function () use ($file, $records) {
            $total = count($records);
            $success = 0;
            $failed = 0;

            $import = Import::create([
                'file_name' => $file->getClientOriginalName(),
                'total_records' => $total,
                'successful_records' => 0,
                'failed_records' => 0,
                'status' => 'failed',
            ]);

            foreach ($records as $record) {
                $validator = Validator::make($record, [
                    'account_number'   => ['required', 'string', new Iban()],
                    'amount'           => ['required', 'numeric', 'gt:0'],
                    'currency'         => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
                    'transaction_id'   => ['required', 'uuid'],
                    'transaction_date' => ['required', 'date'],
                ], [
                    'account_number.required' => 'Account number is required',
                    'amount.gt'               => 'Amount should be more than zero',
                    'currency.size'           => 'Currency code has 3 letters',
                ]);

                if ($validator->fails()) {
                    $failed++;
                    $import->logs()->create([
                        'transaction_id' => $record['transaction_id'] ?? null,
                        'error_message'  => implode('; ', $validator->errors()->all()),
                    ]);
                } else {
                    $success++;
                    Transaction::create([
                        'transaction_id'   => $record['transaction_id'],
                        'account_number'   => strtoupper(str_replace(' ', '', $record['account_number'])),
                        'transaction_date' => $record['transaction_date'],
                        'amount'           => $record['amount'],
                        'currency'         => strtoupper($record['currency']),
                    ]);
                }
            }

            $status = 'failed';
            if ($success === $total && $total > 0) {
                $status = 'success';
            } elseif ($success > 0) {
                $status = 'partial';
            }

            $import->update([
                'successful_records' => $success,
                'failed_records'     => $failed,
                'status'             => $status,
            ]);

            return $import;
        });
    }
}
