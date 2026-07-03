<?php

use App\Http\Controllers\CustomCategoryController;
use App\Http\Controllers\GoldPriceController;
use App\Http\Controllers\KontrakCicilanController;
use App\Http\Controllers\PortofolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [PortofolioController::class, 'index'])->name('dashboard');

    // Catat
    Route::get('/catat', [PortofolioController::class, 'create'])->name('portofolio.create');

    // Portofolio CRUD
    Route::get('/api/catat-context', [PortofolioController::class, 'catatContext'])->name('catat.context');
    Route::post('/portofolio', [PortofolioController::class, 'store'])->name('portofolio.store');
    Route::put('/portofolio/{portofolio}', [PortofolioController::class, 'update'])->name('portofolio.update');
    Route::delete('/portofolio/{portofolio}', [PortofolioController::class, 'destroy'])->name('portofolio.destroy');

    // Kontrak cicilan emas
    Route::get('/kontrak-cicilan', [KontrakCicilanController::class, 'index'])->name('kontrak-cicilan.index');
    Route::post('/kontrak-cicilan', [KontrakCicilanController::class, 'store'])->name('kontrak-cicilan.store');
    Route::put('/kontrak-cicilan/{kontrak}', [KontrakCicilanController::class, 'update'])->name('kontrak-cicilan.update');
    Route::delete('/kontrak-cicilan/{kontrak}', [KontrakCicilanController::class, 'destroy'])->name('kontrak-cicilan.destroy');
    Route::get('/kontrak-cicilan/{kontrak}/file', [KontrakCicilanController::class, 'file'])->name('kontrak-cicilan.file');

    // Grafik
    Route::get('/grafik', [PortofolioController::class, 'grafik'])->name('grafik');

    // Info
    Route::get('/info', [PortofolioController::class, 'info'])->name('info');

    // Target
    Route::get('/target', [TargetController::class, 'index'])->name('target');
    Route::put('/target', [TargetController::class, 'update'])->name('target.update');

    // Keuangan (pengeluaran & pemasukan)
    Route::get('/keuangan', [TransactionController::class, 'index'])->name('keuangan.index');
    Route::post('/keuangan', [TransactionController::class, 'store'])->name('keuangan.store');
    Route::post('/keuangan/budget', [TransactionController::class, 'storeBudget'])->name('keuangan.budget');
    Route::delete('/keuangan/budget/{kategori}', [TransactionController::class, 'destroyBudget'])->name('keuangan.budget.destroy');
    Route::delete('/keuangan/{transaction}', [TransactionController::class, 'destroy'])->name('keuangan.destroy');

    // Recurring transactions
    Route::post('/keuangan/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::patch('/keuangan/recurring/{recurring}/toggle', [RecurringTransactionController::class, 'toggle'])->name('recurring.toggle');
    Route::delete('/keuangan/recurring/{recurring}', [RecurringTransactionController::class, 'destroy'])->name('recurring.destroy');
    Route::post('/keuangan/recurring/apply', [RecurringTransactionController::class, 'apply'])->name('recurring.apply');

    // Custom categories
    Route::post('/keuangan/kategori', [CustomCategoryController::class, 'store'])->name('kategori.store');
    Route::delete('/keuangan/kategori/{category}', [CustomCategoryController::class, 'destroy'])->name('kategori.destroy');

    // Proxy harga emas
    Route::get('/api/harga-emas', [GoldPriceController::class, 'index'])->middleware('throttle:30,1');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';
