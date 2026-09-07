<?php

declare(strict_types=1);

namespace Tests\Application\Transaction\UseCases;

use App\Application\Transaction\UseCases\GetImportsUseCase;
use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Repositories\ImportRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetImportsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_array_when_no_imports_exist(): void
    {
        $useCase = app(GetImportsUseCase::class);
        $imports = $useCase->execute();

        $this->assertEmpty($imports);
    }

    public function test_can_retrieve_all_imports_ordered_by_latest(): void
    {
        $importRepository = app(ImportRepositoryInterface::class);

        $firstImport = Import::startNew('first.csv')
            ->withProcessedResults(totalRecords: 5, successfulRecords: 5);
        $importRepository->save($firstImport);

        $secondImport = Import::startNew('second.json')
            ->withProcessedResults(totalRecords: 10, successfulRecords: 8);
        $importRepository->save($secondImport);

        $useCase = app(GetImportsUseCase::class);
        $allImports = $useCase->execute();

        $this->assertCount(2, $allImports);
        $this->assertSame('second.json', $allImports[0]->fileName);
        $this->assertSame('first.csv', $allImports[1]->fileName);
    }
}
