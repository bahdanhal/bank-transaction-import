<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Entities\ImportLog;
use App\Domain\Transaction\ValueObjects\ImportStatus;
use PHPUnit\Framework\TestCase;

final class ImportAggregateTest extends TestCase
{
    public function test_can_initialize_new_import(): void
    {
        $import = Import::startNew('transactions.csv');

        $this->assertSame('transactions.csv', $import->fileName);
        $this->assertSame(0, $import->totalRecords);
        $this->assertSame(0, $import->successfulRecords);
        $this->assertSame(1, $import->failedRecords);
        $this->assertSame(ImportStatus::FAILED, $import->status);
    }

    public function test_can_finish_processing_with_success(): void
    {
        $import = Import::startNew('transactions.csv')
            ->withProcessedResults(totalRecords: 10, successfulRecords: 10);

        $this->assertSame(10, $import->totalRecords);
        $this->assertSame(10, $import->successfulRecords);
        $this->assertSame(0, $import->failedRecords);
        $this->assertSame(ImportStatus::SUCCESS, $import->status);
    }

    public function test_can_finish_processing_with_partial_status(): void
    {
        $import = Import::startNew('transactions.csv')
            ->withTransactionFailure('transaction-1', 'Invalid date')
            ->withProcessedResults(totalRecords: 10, successfulRecords: 8);

        $this->assertSame(10, $import->totalRecords);
        $this->assertSame(8, $import->successfulRecords);
        $this->assertSame(2, $import->failedRecords);
        $this->assertSame(ImportStatus::PARTIAL, $import->status);
        $this->assertCount(1, $import->logs);
    }

    public function test_can_record_fatal_error(): void
    {
        $import = Import::startNew('broken.json')
            ->withFatalError('Invalid JSON syntax');

        $this->assertSame(ImportStatus::FAILED, $import->status);
        $this->assertSame(0, $import->successfulRecords);
        $this->assertSame(1, $import->failedRecords);
        $this->assertCount(1, $import->logs);
        $this->assertSame('Invalid JSON syntax', $import->logs[0]->errorMessage);
    }

    public function test_with_id_wither_creates_new_instance(): void
    {
        $import = Import::startNew('test.csv');
        $importWithDatabaseId = $import->withId(42);

        $this->assertNull($import->id);
        $this->assertSame(42, $importWithDatabaseId->id);
        $this->assertNotSame($import, $importWithDatabaseId);
    }

    public function test_import_log_withers(): void
    {
        $importLog = new ImportLog('Test error');
        $importLogWithId = $importLog->withId(10)->withImportId(20);

        $this->assertNull($importLog->id);
        $this->assertNull($importLog->importId);
        $this->assertSame(10, $importLogWithId->id);
        $this->assertSame(20, $importLogWithId->importId);
        $this->assertNotSame($importLog, $importLogWithId);
    }

    public function test_import_property_hooks(): void
    {
        $successfulImport = Import::startNew('transactions.csv')
            ->withProcessedResults(totalRecords: 10, successfulRecords: 10);

        $this->assertTrue($successfulImport->isSuccessful);
        $this->assertFalse($successfulImport->hasFailures);
        $this->assertSame(10, $successfulImport->processedRecords);

        $failedImport = Import::startNew('transactions.csv')
            ->withProcessedResults(totalRecords: 10, successfulRecords: 5);

        $this->assertFalse($failedImport->isSuccessful);
        $this->assertTrue($failedImport->hasFailures);
        $this->assertSame(10, $failedImport->processedRecords);
    }

    public function test_import_log_property_hooks(): void
    {
        $logWithTransaction = new ImportLog('Test error', '550e8400-e29b-41d4-a716-446655440000');
        $this->assertTrue($logWithTransaction->hasTransactionId);

        $logWithoutTransaction = new ImportLog('Fatal file error');
        $this->assertFalse($logWithoutTransaction->hasTransactionId);
    }
}
