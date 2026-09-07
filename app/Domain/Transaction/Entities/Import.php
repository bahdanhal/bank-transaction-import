<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Entities;

use App\Domain\Transaction\ValueObjects\ImportStatus;
use NoDiscard;

final class Import
{
    /**
     * @param array<int, ImportLog> $logs
     */
    public function __construct(
        private(set) string $fileName,
        private(set) int $totalRecords = 0,
        private(set) int $successfulRecords = 0,
        private(set) int $failedRecords = 0,
        private(set) ImportStatus $status = ImportStatus::FAILED,
        private(set) array $logs = [],
        private(set) ?int $id = null,
        private(set) ?string $createdAt = null,
        private(set) ?string $updatedAt = null
    ) {
    }

    public bool $isSuccessful {
        get => $this->status === ImportStatus::SUCCESS;
    }

    public bool $hasFailures {
        get => $this->failedRecords > 0;
    }

    public int $processedRecords {
        get => $this->successfulRecords + $this->failedRecords;
    }

    public static function startNew(string $fileName): self
    {
        return new self(
            fileName: $fileName,
            totalRecords: 0,
            successfulRecords: 0,
            failedRecords: 1,
            status: ImportStatus::FAILED
        );
    }

    #[\NoDiscard]
    public function withId(int $id): self
    {
        return clone($this, ['id' => $id]);
    }

    #[\NoDiscard]
    public function withLog(ImportLog $log): self
    {
        return clone($this, [
            'logs' => [...$this->logs, $log],
        ]);
    }

    /**
     * @param array<int, ImportLog> $logs
     */
    #[\NoDiscard]
    public function withLogs(array $logs): self
    {
        return clone($this, ['logs' => $logs]);
    }

    #[\NoDiscard]
    public function withFatalError(string $message): self
    {
        return clone($this, [
            'totalRecords'      => 0,
            'successfulRecords' => 0,
            'failedRecords'     => 1,
            'status'            => ImportStatus::FAILED,
            'logs'              => [...$this->logs, new ImportLog(errorMessage: $message)],
        ]);
    }

    #[\NoDiscard]
    public function withTransactionFailure(?string $transactionId, string $message): self
    {
        return clone($this, [
            'logs' => [...$this->logs, new ImportLog(errorMessage: $message, transactionId: $transactionId)],
        ]);
    }

    #[\NoDiscard]
    public function withProcessedResults(int $totalRecords, int $successfulRecords): self
    {
        $failedRecords = max(0, $totalRecords - $successfulRecords);

        return clone($this, [
            'totalRecords'      => $totalRecords,
            'successfulRecords' => $successfulRecords,
            'failedRecords'     => $failedRecords,
            'status'            => ImportStatus::fromCounts($totalRecords, $successfulRecords, $failedRecords),
        ]);
    }


    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'file_name'          => $this->fileName,
            'total_records'      => $this->totalRecords,
            'successful_records' => $this->successfulRecords,
            'failed_records'     => $this->failedRecords,
            'status'             => $this->status->value,
            'created_at'         => $this->createdAt,
            'updated_at'         => $this->updatedAt,
            'logs'               => array_map(static fn (ImportLog $log): array => $log->toArray(), $this->logs),
        ];
    }
}
