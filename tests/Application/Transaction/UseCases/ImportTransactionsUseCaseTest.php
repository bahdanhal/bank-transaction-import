<?php

declare(strict_types=1);

namespace Tests\Application\Transaction\UseCases;

use App\Application\Transaction\UseCases\ImportTransactionsUseCase;
use App\Domain\Transaction\ValueObjects\ImportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ImportTransactionsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private string $validIban = 'PL61109010140000071219812874';

    public function test_can_import_valid_transactions_successfully(): void
    {
        $firstTransactionId = (string) Str::uuid();
        $secondTransactionId = (string) Str::uuid();

        $csvContent = implode("\n", [
            'transaction_id,account_number,transaction_date,amount,currency',
            "{$firstTransactionId},{$this->validIban},2025-10-14,150.00,PLN",
            "{$secondTransactionId},{$this->validIban},2025-10-13,200.50,USD",
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('valid_import.csv', $csvContent);

        $useCase = app(ImportTransactionsUseCase::class);
        $importResult = $useCase->execute($uploadedFile);

        $this->assertSame(ImportStatus::SUCCESS, $importResult->status);
        $this->assertSame(2, $importResult->totalRecords);
        $this->assertSame(2, $importResult->successfulRecords);
        $this->assertSame(0, $importResult->failedRecords);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $firstTransactionId]);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $secondTransactionId]);
    }

    public function test_can_import_with_partial_failures(): void
    {
        $validTransactionId = (string) Str::uuid();

        $csvContent = implode("\n", [
            'transaction_id,account_number,transaction_date,amount,currency',
            "{$validTransactionId},{$this->validIban},2025-10-14,150.00,PLN",
            "not-a-uuid,{$this->validIban},2025-10-14,100.00,PLN",
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('partial_import.csv', $csvContent);

        $useCase = app(ImportTransactionsUseCase::class);
        $importResult = $useCase->execute($uploadedFile);

        $this->assertSame(ImportStatus::PARTIAL, $importResult->status);
        $this->assertSame(2, $importResult->totalRecords);
        $this->assertSame(1, $importResult->successfulRecords);
        $this->assertSame(1, $importResult->failedRecords);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('import_logs', 1);
    }

    public function test_handles_empty_file_with_no_data_rows(): void
    {
        $csvContent = 'transaction_id,account_number,transaction_date,amount,currency';
        $uploadedFile = UploadedFile::fake()->createWithContent('empty.csv', $csvContent);

        $useCase = app(ImportTransactionsUseCase::class);
        $importResult = $useCase->execute($uploadedFile);

        $this->assertSame(ImportStatus::FAILED, $importResult->status);
        $this->assertSame(0, $importResult->totalRecords);
        $this->assertSame(1, $importResult->failedRecords);
        $this->assertCount(1, $importResult->logs);
        $this->assertSame('No valid transaction records found in file.', $importResult->logs[0]->errorMessage);
    }

    public function test_handles_unsupported_file_extension_gracefully(): void
    {
        $uploadedFile = UploadedFile::fake()->create('report.pdf', 100);

        $useCase = app(ImportTransactionsUseCase::class);
        $importResult = $useCase->execute($uploadedFile);

        $this->assertSame(ImportStatus::FAILED, $importResult->status);
        $this->assertCount(1, $importResult->logs);
        $this->assertStringContainsString('Unsupported file format', $importResult->logs[0]->errorMessage);
    }

    public function test_streams_and_chunks_large_batch_of_transactions(): void
    {
        $csvRows = ['transaction_id,account_number,transaction_date,amount,currency'];
        $generatedRecordCount = 550;

        for ($index = 1; $index <= $generatedRecordCount; $index++) {
            $transactionId = (string) Str::uuid();
            $csvRows[] = "{$transactionId},{$this->validIban},2025-10-14,10.00,PLN";
        }

        $uploadedFile = UploadedFile::fake()->createWithContent('large_batch.csv', implode("\n", $csvRows));

        $useCase = app(ImportTransactionsUseCase::class);
        $importResult = $useCase->execute($uploadedFile);

        $this->assertSame(ImportStatus::SUCCESS, $importResult->status);
        $this->assertSame($generatedRecordCount, $importResult->totalRecords);
        $this->assertSame($generatedRecordCount, $importResult->successfulRecords);
        $this->assertSame(0, $importResult->failedRecords);
        $this->assertDatabaseCount('transactions', $generatedRecordCount);
    }
}
