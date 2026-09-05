<?php

use App\Http\Controllers\ProfileController;
use App\Models\Import;
use App\Services\TransactionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Imports', [
        'imports' => Import::with('logs')->latest()->get(),
    ]);
})->name('imports.index');

Route::post('/imports', function (Request $request, TransactionImportService $service) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt,json,xml|max:10240',
    ]);

    $service->handle($request->file('file'));

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
