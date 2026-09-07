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

            if (empty($records)) {
                throw new \RuntimeException('No valid transaction records found in file.');
            }
        } catch (Throwable $exception) {
            $import = $import->withFatalError($exception->getMessage());
            return $this->importRepository->save($import);
        }

        return DB::transaction(function () use ($import, $records) {
            $validTransactions = [];
            $successfulCount = 0;
            $currentImport = $import;

            foreach ($records as $record) {
                if (!is_array($record)) {
                    $currentImport = $currentImport->withTransactionFailure(
                        null,
                        'Invalid transaction format: expected object/array, received ' . gettype($record)
                    );
                    continue;
                }

                try {
                    $transaction = Transaction::createFromRaw($record);
                    $validTransactions[] = $transaction;
                    $successfulCount++;
                } catch (InvalidTransactionException $exception) {
                    $currentImport = $currentImport->withTransactionFailure(
                        $exception->transactionId,
                        $exception->getMessage()
                    );
                }
            }

            if (!empty($validTransactions)) {
                $this->transactionRepository->saveMany($validTransactions);
            }

            $currentImport = $currentImport->withProcessedResults(count($records), $successfulCount);

            return $this->importRepository->save($currentImport);
        });
    }
}
