<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PortofolioController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

// Redirect root ke dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [PortofolioController::class, 'index'])->name('dashboard');

    // Catat
    Route::get('/catat', fn() => Inertia::render('Catat'))->name('portofolio.create');

    // Portofolio CRUD
    Route::post('/portofolio', [PortofolioController::class, 'store'])->name('portofolio.store');
    Route::get('/portofolio/{portofolio}/edit', [PortofolioController::class, 'edit'])->name('portofolio.edit');
    Route::put('/portofolio/{portofolio}', [PortofolioController::class, 'update'])->name('portofolio.update');
    Route::delete('/portofolio/{portofolio}', [PortofolioController::class, 'destroy'])->name('portofolio.destroy');

    // Grafik
    Route::get('/grafik', function () {
        $data = \App\Models\Portofolio::where('user_id', auth()->id())->orderBy('bulan')->get();
        return Inertia::render('Grafik', ['portofolios' => $data]);
    })->name('grafik');

    // Info
    Route::get('/info', fn() => Inertia::render('Info'))->name('info');

    // Target
    Route::get('/target', [TargetController::class, 'index'])->name('target');
    Route::put('/target', [TargetController::class, 'update'])->name('target.update');

    // Keuangan (pengeluaran & pemasukan)
    Route::get('/keuangan', [TransactionController::class, 'index'])->name('keuangan.index');
    Route::post('/keuangan', [TransactionController::class, 'store'])->name('keuangan.store');
    Route::post('/keuangan/budget', [TransactionController::class, 'storeBudget'])->name('keuangan.budget');
    Route::delete('/keuangan/{transaction}', [TransactionController::class, 'destroy'])->name('keuangan.destroy');

    // Proxy harga emas
    Route::get('/api/harga-emas', function () {
        try {
            $kurs = Http::get('https://api.frankfurter.app/latest?from=USD&to=IDR');
            $usdToIdr = $kurs->json()['rates']['IDR'];

            $xau = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://data-asg.goldprice.org/dbXRates/USD');
            $xauUsd = $xau->json()['items'][0]['xauPrice'] ?? 3280;

            $perGramUsd = $xauUsd / 31.1035;
            $perGramIdr = round($perGramUsd * $usdToIdr);
            $pegadaian  = round($perGramIdr * 1.04);

            return response()->json([
                'success'   => true,
                'xau_usd'   => round($xauUsd, 2),
                'usd_idr'   => round($usdToIdr),
                'spot_idr'  => $perGramIdr,
                'pegadaian' => $pegadaian,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';