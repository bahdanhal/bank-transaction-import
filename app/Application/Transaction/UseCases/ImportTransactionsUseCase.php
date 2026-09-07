<?php

declare(strict_types=1);

namespace App\Application\Transaction\UseCases;

use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Entities\Transaction;
use App\Domain\Transaction\Exceptions\InvalidTransactionException;
use App\Domain\Transaction\Repositories\ImportRepositoryInterface;
use App\Domain\Transaction\Repositories\TransactionRepositoryInterface;
use App\Infrastructure\Transaction\Parsers\TransactionParserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ImportTransactionsUseCase
{
    public function __construct(
        private TransactionParserFactory $parserFactory,
        private TransactionRepositoryInterface $transactionRepository,
        private ImportRepositoryInterface $importRepository
    ) {
    }

    public function execute(UploadedFile $file): Import
    {
        $import = Import::startNew($file->getClientOriginalName());
        $import = $this->importRepository->save($import);

        try {
            $parser = $this->parserFactory->make($file->getClientOriginalExtension());
            $records = $parser->parse($file);

            return DB::transaction(function () use ($import, $records) {
                $batchOfTransactions = [];
                $successfulCount = 0;
                $totalRecords = 0;
                $currentImport = $import;

                foreach ($records as $record) {
                    $totalRecords++;
                    if (!is_array($record)) {
                        $currentImport = $currentImport->withTransactionFailure(
                            null,
                            'Invalid transaction format: expected object/array, received ' . gettype($record)
                        );
                        continue;
                    }

                    try {
                        $transaction = Transaction::createFromRaw($record);
                        $batchOfTransactions[] = $transaction;
                        $successfulCount++;

                        if (count($batchOfTransactions) >= 500) {
                            $this->transactionRepository->saveMany($batchOfTransactions);
                            $batchOfTransactions = [];
                        }
                    } catch (InvalidTransactionException $exception) {
                        $currentImport = $currentImport->withTransactionFailure(
                            $exception->transactionId,
                            $exception->getMessage()
                        );
                    }
                }

                if ($totalRecords === 0) {
                    $currentImport = $currentImport->withFatalError('No valid transaction records found in file.');
                    return $this->importRepository->save($currentImport);
                }

                if (!empty($batchOfTransactions)) {
                    $this->transactionRepository->saveMany($batchOfTransactions);
                }

                $currentImport = $currentImport->withProcessedResults($totalRecords, $successfulCount);

                return $this->importRepository->save($currentImport);
            });
        } catch (Throwable $exception) {
            $import = $import->withFatalError($exception->getMessage());
            return $this->importRepository->save($import);
        }
    }
}
