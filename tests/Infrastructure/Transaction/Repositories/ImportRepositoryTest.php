<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Transaction\Repositories;

use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\ValueObjects\ImportStatus;
use App\Infrastructure\Transaction\Repositories\ImportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_new_import_aggregate(): void
    {
        $repository = new ImportRepository();
        $import = Import::startNew('initial_import.csv');

        $savedImport = $repository->save($import);

        $this->assertNotNull($savedImport->id);
        $this->assertSame('initial_import.csv', $savedImport->fileName);
        $this->assertDatabaseHas('imports', [
            'id'        => $savedImport->id,
            'file_name' => 'initial_import.csv',
            'status'    => 'failed',
        ]);
    }

    public function test_can_update_existing_import_with_logs(): void
    {
        $repository = new ImportRepository();
        $import = Import::startNew('processing.csv');
        $savedImport = $repository->save($import);

        $updatedImport = $savedImport
            ->withTransactionFailure('550e8400-e29b-41d4-a716-446655440000', 'Invalid IBAN checksum')
            ->withProcessedResults(totalRecords: 10, successfulRecords: 9);

        $persistedImport = $repository->save($updatedImport);

        $this->assertSame($savedImport->id, $persistedImport->id);
        $this->assertSame(ImportStatus::PARTIAL, $persistedImport->status);
        $this->assertSame(10, $persistedImport->totalRecords);
        $this->assertSame(9, $persistedImport->successfulRecords);
        $this->assertSame(1, $persistedImport->failedRecords);
        $this->assertCount(1, $persistedImport->logs);

        $this->assertDatabaseHas('imports', [
            'id'     => $savedImport->id,
            'status' => 'partial',
        ]);
        $this->assertDatabaseHas('import_logs', [
            'import_id'     => $savedImport->id,
            'error_message' => 'Invalid IBAN checksum',
        ]);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $repository = new ImportRepository();
        $foundImport = $repository->findById(99999);

        $this->assertNull($foundImport);
    }

    public function test_can_get_all_latest_imports(): void
    {
        $repository = new ImportRepository();

        $firstImport = Import::startNew('first.csv')->withProcessedResults(5, 5);
        $repository->save($firstImport);

        $secondImport = Import::startNew('second.csv')->withProcessedResults(10, 10);
        $repository->save($secondImport);

        $allImports = $repository->getAllLatest();

        $this->assertCount(2, $allImports);
        $this->assertSame('second.csv', $allImports[0]->fileName);
        $this->assertSame('first.csv', $allImports[1]->fileName);
    }
}
