<?php

use App\Domain\Transaction\Entities\Import as DomainImport;
use App\Application\Transaction\UseCases\GetImportsUseCase;
use App\Application\Transaction\UseCases\ImportTransactionsUseCase;
use App\Http\Controllers\ProfileController;
use App\Http\Requests\UploadImportRequest;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (GetImportsUseCase $useCase) {
    $imports = array_map(
        static fn (DomainImport $import): array => $import->toArray(),
        $useCase->execute()
    );

    return Inertia::render('Imports', [
        'imports' => $imports,
    ]);
})->name('imports.index');

Route::post('/imports', function (UploadImportRequest $request, ImportTransactionsUseCase $useCase) {
    $useCase->execute($request->file('file'));

    return redirect()->back();
})->name('imports.store');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
