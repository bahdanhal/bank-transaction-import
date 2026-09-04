<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Services\TransactionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ImportController extends Controller
{
    public function index(): JsonResponse
    {
        $imports = Import::latest()->get();
        return response()->json($imports);
    }

    public function store(Request $request, TransactionImportService $service): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,json,xml|max:10240',
        ]);

        try {
            $import = $service->handle($request->file('file'));
            return response()->json($import->load('logs'), 201);
        } catch (Throwable $exception) {
            return response()->json([
                'error' => 'Processing error: ' . $exception->getMessage()
            ], 422);
        }
    }

    public function show(int $importId): JsonResponse
    {
        $import = Import::with('logs')->findOrFail($importId);
        return response()->json($import);
    }
}
