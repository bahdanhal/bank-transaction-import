<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ImportLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    private string $validIban = 'PL61109010140000071219812874';

    public function test_can_list_all_imports(): void
    {
        Import::create([
            'file_name' => 'test_1.csv',
            'total_records' => 10,
            'successful_records' => 10,
            'failed_records' => 0,
            'status' => 'success',
        ]);

        Import::create([
            'file_name' => 'test_2.json',
            'total_records' => 5,
            'successful_records' => 2,
            'failed_records' => 3,
            'status' => 'partial',
        ]);

        $response = $this->getJson('/api/imports');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['file_name' => 'test_1.csv'])
            ->assertJsonFragment(['file_name' => 'test_2.json']);
    }

    public function test_can_get_single_import_details_with_logs(): void
    {
        $import = Import::create([
            'file_name' => 'failing.csv',
            'total_records' => 1,
            'successful_records' => 0,
            'failed_records' => 1,
            'status' => 'failed',
        ]);

        ImportLog::create([
            'import_id' => $import->id,
            'transaction_id' => '550e8400-e29b-41d4-a716-446655440000',
            'error_message' => 'Amount should be more than zero',
        ]);

        $response = $this->getJson("/api/imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $import->id)
            ->assertJsonPath('status', 'failed')
            ->assertJsonCount(1, 'logs')
            ->assertJsonPath('logs.0.transaction_id', '550e8400-e29b-41d4-a716-446655440000')
            ->assertJsonPath('logs.0.error_message', 'Amount should be more than zero');
    }

    public function test_returns_404_when_import_does_not_exist(): void
    {
        $response = $this->getJson('/api/imports/99999');

        $response->assertStatus(404);
    }

    public function test_can_upload_and_process_valid_csv(): void
    {
        $tx1 = (string) Str::uuid();
        $tx2 = (string) Str::uuid();

        $csvContent = implode("\n", [
            'transaction_id,account_number,transaction_date,amount,currency',
            "{$tx1},{$this->validIban},2025-10-14,150000,PLN",
            "{$tx2},{$this->validIban},2025-10-13,20050,USD"
        ]);

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csvContent);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_records', 2)
            ->assertJsonPath('successful_records', 2)
            ->assertJsonPath('failed_records', 0);

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $tx1, 'currency' => 'PLN']);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $tx2, 'currency' => 'USD']);
        $this->assertDatabaseCount('import_logs', 0);
    }

    public function test_can_process_csv_with_validation_errors(): void
    {
        $validUuid = (string) Str::uuid();
        $invalidIban = 'PL00000000000000000000000000'; // Invalid IBAN checksum

        $csvContent = implode("\n", [
            'transaction_id,account_number,transaction_date,amount,currency',
            "{$validUuid},{$this->validIban},2026-03-01,200.00,PLN",
            Str::uuid() . ",{$invalidIban},2026-03-01,100.00,PLN",
            Str::uuid() . ",{$this->validIban},2026-03-01,-50.00,PLN",
            "not-a-valid-uuid,{$this->validIban},2026-03-01,100.00,POLAND"
        ]);

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csvContent);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'partial')
            ->assertJsonPath('total_records', 4)
            ->assertJsonPath('successful_records', 1)
            ->assertJsonPath('failed_records', 3);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $validUuid]);

        $this->assertDatabaseCount('import_logs', 3);
        $this->assertDatabaseHas('import_logs', ['transaction_id' => 'not-a-valid-uuid']);
    }

    public function test_can_upload_and_process_valid_json(): void
    {
        $tx1 = (string) Str::uuid();
        $tx2 = (string) Str::uuid();

        $jsonContent = json_encode([
            [
                'transaction_id' => $tx1,
                'account_number' => $this->validIban,
                'transaction_date' => '2025-10-14',
                'amount' => 150000,
                'currency' => 'PLN'
            ],
            [
                'transaction_id' => $tx2,
                'account_number' => $this->validIban,
                'transaction_date' => '2025-10-13',
                'amount' => 20050,
                'currency' => 'USD'
            ]
        ]);

        $file = UploadedFile::fake()->createWithContent('data.json', $jsonContent);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_records', 2)
            ->assertJsonPath('successful_records', 2);

        $this->assertDatabaseHas('transactions', ['transaction_id' => $tx1, 'currency' => 'PLN']);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $tx2, 'currency' => 'USD']);
    }

    public function test_can_upload_and_process_valid_xml(): void
    {
        $tx1 = (string) Str::uuid();
        $tx2 = (string) Str::uuid();

        $xmlContent = <<<XML
        <transactions>
          <transaction>
            <transaction_id>{$tx1}</transaction_id>
            <account_number>{$this->validIban}</account_number>
            <transaction_date>2025-10-14</transaction_date>
            <amount>150000</amount>
            <currency>PLN</currency>
          </transaction>
          <transaction>
            <transaction_id>{$tx2}</transaction_id>
            <account_number>{$this->validIban}</account_number>
            <transaction_date>2025-10-13</transaction_date>
            <amount>20050</amount>
            <currency>USD</currency>
          </transaction>
        </transactions>
        XML;

        $file = UploadedFile::fake()->createWithContent('data.xml', $xmlContent);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total_records', 2)
            ->assertJsonPath('successful_records', 2);

        $this->assertDatabaseHas('transactions', ['transaction_id' => $tx1, 'currency' => 'PLN']);
        $this->assertDatabaseHas('transactions', ['transaction_id' => $tx2, 'currency' => 'USD']);
    }

    public function test_fails_when_no_file_uploaded(): void
    {
        $response = $this->postJson('/api/imports');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_fails_when_unsupported_file_extension_uploaded(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
