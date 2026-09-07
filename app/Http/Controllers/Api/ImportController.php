<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\Transaction\UseCases\GetImportDetailsUseCase;
use App\Application\Transaction\UseCases\GetImportsUseCase;
use App\Application\Transaction\UseCases\ImportTransactionsUseCase;
use App\Domain\Transaction\Entities\Import;
use App\Domain\Transaction\Exceptions\ImportNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImportRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ImportController extends Controller
{
    public function index(GetImportsUseCase $useCase): JsonResponse
    {
        $imports = $useCase->execute();

        return response()->json(
            array_map(static fn (Import $import): array => $import->toArray(), $imports)
        );
    }

    public function store(UploadImportRequest $request, ImportTransactionsUseCase $useCase): JsonResponse
    {
        try {
            $import = $useCase->execute($request->file('file'));

            return response()->json($import->toArray(), 201);
        } catch (Throwable $exception) {
            return response()->json([
                'error' => 'Processing error: ' . $exception->getMessage(),
            ], 422);
        }
    }

    public function show(int $id, GetImportDetailsUseCase $useCase): JsonResponse
    {
        try {
            $import = $useCase->execute($id);

            return response()->json($import->toArray());
        } catch (ImportNotFoundException) {
            abort(404, "Import not found.");
        }
    }
}
