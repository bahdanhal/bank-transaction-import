<?php

declare(strict_types=1);

namespace App\Domain\Transaction\Entities;

use NoDiscard;

final class ImportLog
{
    public function __construct(
        private(set) string $errorMessage,
        private(set) ?string $transactionId = null,
        private(set) ?int $importId = null,
        private(set) ?int $id = null,
        private(set) ?string $createdAt = null
    ) {
    }

    public bool $hasTransactionId {
        get => $this->transactionId !== null;
    }

    #[\NoDiscard]
    public function withId(int $id): self
    {
        return clone($this, ['id' => $id]);
    }

    #[\NoDiscard]
    public function withImportId(int $importId): self
    {
        return clone($this, ['importId' => $importId]);
    }



    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'import_id'      => $this->importId,
            'transaction_id' => $this->transactionId,
            'error_message'  => $this->errorMessage,
            'created_at'     => $this->createdAt,
        ];
    }
}
