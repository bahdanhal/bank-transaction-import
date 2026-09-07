<?php

declare(strict_types=1);

namespace App\Application\Transaction\UseCases;

use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Repositories\ImportRepositoryInterface;

final readonly class GetImportsUseCase
{
    public function __construct(
        private ImportRepositoryInterface $importRepository
    ) {
    }

    /**
     * @return array<int, Import>
     */
    public function execute(): array
    {
        return $this->importRepository->getAllLatest();
    }
}
