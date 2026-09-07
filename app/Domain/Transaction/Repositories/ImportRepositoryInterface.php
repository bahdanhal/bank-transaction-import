<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Repositories;

use App\Domain\Transaction\Entities\Import;

interface ImportRepositoryInterface
{
    public function save(Import $import): Import;

    public function findById(int $id): ?Import;

    /**
     * @return array<int, Import>
     */
    public function getAllLatest(): array;
}
