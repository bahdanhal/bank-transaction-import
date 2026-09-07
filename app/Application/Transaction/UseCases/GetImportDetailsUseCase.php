<?php

declare(strict_types=1);

namespace App\Application\Transaction\UseCases;

use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Exceptions\ImportNotFoundException;
use App\Domain\Transaction\Repositories\ImportRepositoryInterface;

final readonly class GetImportDetailsUseCase
{
    public function __construct(
        private ImportRepositoryInterface $importRepository
    ) {
    }

    public function execute(int $id): Import
    {
        $import = $this->importRepository->findById($id);

        if ($import === null) {
            throw ImportNotFoundException::forId($id);
        }

        return $import;
    }
}
