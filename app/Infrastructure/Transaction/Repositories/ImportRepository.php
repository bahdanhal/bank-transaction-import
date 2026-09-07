<?php

declare(strict_types=1);

namespace App\Infrastructure\Transaction\Repositories;

use App\Domain\Transaction\Entities\Import as DomainImport;
use App\Domain\Transaction\Entities\ImportLog as DomainImportLog;
use App\Domain\Transaction\Repositories\ImportRepositoryInterface;
use App\Domain\Transaction\ValueObjects\ImportStatus;
use App\Models\Import as ImportModel;
use App\Models\ImportLog as ImportLogModel;

final readonly class ImportRepository implements ImportRepositoryInterface
{
    public function save(DomainImport $import): DomainImport
    {
        if ($import->id === null) {
            $importModel = ImportModel::create([
                'file_name'          => $import->fileName,
                'total_records'      => $import->totalRecords,
                'successful_records' => $import->successfulRecords,
                'failed_records'     => $import->failedRecords,
                'status'             => $import->status->value,
            ]);
        } else {
            $importModel = ImportModel::findOrFail($import->id);
            $importModel->update([
                'total_records'      => $import->totalRecords,
                'successful_records' => $import->successfulRecords,
                'failed_records'     => $import->failedRecords,
                'status'             => $import->status->value,
            ]);
        }

        $logsToInsert = [];
        $currentTimestamp = now();
        foreach ($import->logs as $importLog) {
            if ($importLog->id === null) {
                $logsToInsert[] = [
                    'import_id'      => $importModel->id,
                    'transaction_id' => $importLog->transactionId,
                    'error_message'  => $importLog->errorMessage,
                    'created_at'     => $currentTimestamp,
                    'updated_at'     => $currentTimestamp,
                ];
            }
        }

        if (!empty($logsToInsert)) {
            foreach (array_chunk($logsToInsert, 500) as $chunkOfLogs) {
                ImportLogModel::insert($chunkOfLogs);
            }
        }

        return $this->toDomain($importModel->fresh(['logs']));
    }

    public function findById(int $importId): ?DomainImport
    {
        $importModel = ImportModel::with('logs')->find($importId);

        return $importModel ? $this->toDomain($importModel) : null;
    }

    /**
     * @return array<int, DomainImport>
     */
    public function getAllLatest(): array
    {
        return ImportModel::with('logs')
            ->latest()
            ->get()
            ->map(fn (ImportModel $importModel) => $this->toDomain($importModel))
            ->all();
    }

    private function toDomain(ImportModel $importModel): DomainImport
    {
        $logs = [];
        foreach ($importModel->logs as $logModel) {
            $logs[] = new DomainImportLog(
                errorMessage: $logModel->error_message,
                transactionId: $logModel->transaction_id,
                importId: $logModel->import_id,
                id: $logModel->id,
                createdAt: $logModel->created_at?->toISOString()
            );
        }

        return new DomainImport(
            fileName: $importModel->file_name,
            totalRecords: (int) $importModel->total_records,
            successfulRecords: (int) $importModel->successful_records,
            failedRecords: (int) $importModel->failed_records,
            status: ImportStatus::from($importModel->status),
            logs: $logs,
            id: $importModel->id,
            createdAt: $importModel->created_at?->toISOString(),
            updatedAt: $importModel->updated_at?->toISOString()
        );
    }
}
