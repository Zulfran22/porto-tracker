<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PortofolioController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\CustomCategoryController;
use App\Http\Controllers\KontrakCicilanController;
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
    Route::get('/catat', function () {
        $last = \App\Models\Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan', 'desc')->first();

        $aktifKontrak = \App\Models\KontrakCicilanEmas::where('user_id', auth()->id())
            ->where('status', 'aktif')
            ->orderBy('tanggal_mulai', 'desc')
            ->first();

        return Inertia::render('Catat', [
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'aktifKontrak'  => $aktifKontrak,
        ]);
    })->name('portofolio.create');

    // Portofolio CRUD
    Route::post('/portofolio', [PortofolioController::class, 'store'])->name('portofolio.store');
    Route::get('/portofolio/{portofolio}/edit', [PortofolioController::class, 'edit'])->name('portofolio.edit');
    Route::put('/portofolio/{portofolio}', [PortofolioController::class, 'update'])->name('portofolio.update');
    Route::delete('/portofolio/{portofolio}', [PortofolioController::class, 'destroy'])->name('portofolio.destroy');

    // Kontrak cicilan emas
    Route::get('/kontrak-cicilan', [KontrakCicilanController::class, 'index'])->name('kontrak-cicilan.index');
    Route::post('/kontrak-cicilan', [KontrakCicilanController::class, 'store'])->name('kontrak-cicilan.store');
    Route::put('/kontrak-cicilan/{kontrak}', [KontrakCicilanController::class, 'update'])->name('kontrak-cicilan.update');
    Route::delete('/kontrak-cicilan/{kontrak}', [KontrakCicilanController::class, 'destroy'])->name('kontrak-cicilan.destroy');

    // Grafik
    Route::get('/grafik', function () {
        $data = \App\Models\Portofolio::where('user_id', auth()->id())->orderBy('bulan')->get();
        return Inertia::render('Grafik', [
            'portofolios'  => $data,
            'aktifKontrak' => \App\Models\KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    })->name('grafik');

    // Info
    Route::get('/info', function () {
        $last = \App\Models\Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan', 'desc')->first();
        return Inertia::render('Info', [
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'lastCicilan'   => $last ? (int) $last->cicilan : null,
            'aktifKontrak'  => \App\Models\KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    })->name('info');

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
    Route::get('/api/harga-emas', function () {
        try {
            $kurs = Http::timeout(5)->get('https://api.frankfurter.app/latest?from=USD&to=IDR');
            $usdToIdr = $kurs->json()['rates']['IDR'];

            $xau = Http::timeout(5)->withHeaders(['User-Agent' => 'Mozilla/5.0'])
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
            report($e);
            return response()->json(['success' => false, 'message' => 'Gagal mengambil harga emas terbaru.'], 502);
        }
    })->middleware('throttle:30,1');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';