<?php

declare(strict_types=1);

namespace Tests\Application\Transaction\UseCases;

use App\Application\Transaction\UseCases\GetImportDetailsUseCase;
use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Exceptions\ImportNotFoundException;
use App\Domain\Transaction\Repositories\ImportRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetImportDetailsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_existing_import_details(): void
    {
        $importRepository = app(ImportRepositoryInterface::class);
        $savedImport = $importRepository->save(
            Import::startNew('sample.csv')
                ->withTransactionFailure('550e8400-e29b-41d4-a716-446655440000', 'Amount should be more than zero')
                ->withProcessedResults(totalRecords: 1, successfulRecords: 0)
        );

        $useCase = app(GetImportDetailsUseCase::class);
        $retrievedImport = $useCase->execute($savedImport->id);

        $this->assertSame($savedImport->id, $retrievedImport->id);
        $this->assertSame('sample.csv', $retrievedImport->fileName);
        $this->assertCount(1, $retrievedImport->logs);
        $this->assertSame('Amount should be more than zero', $retrievedImport->logs[0]->errorMessage);
    }

    public function test_throws_exception_when_import_is_not_found(): void
    {
        $useCase = app(GetImportDetailsUseCase::class);

        $this->expectException(ImportNotFoundException::class);
        $this->expectExceptionMessage('Import with ID 99999 not found.');

        $useCase->execute(99999);
    }
}
