<?php

namespace App\Http\Controllers;

use App\Models\Portofolio;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortofolioController extends Controller
{
    // Halaman dashboard
    public function index()
    {
        $data = Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan', 'asc')
            ->get();

        $cashflow = Transaction::where('user_id', auth()->id())
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->get();

        return Inertia::render('Dashboard', [
            'portofolios' => $data,
            'cashflow'    => [
                'income'  => $cashflow->where('type', 'income')->sum('jumlah'),
                'expense' => $cashflow->where('type', 'expense')->sum('jumlah'),
                'net'     => $cashflow->where('type', 'income')->sum('jumlah')
                           - $cashflow->where('type', 'expense')->sum('jumlah'),
            ],
        ]);
    }

    // Simpan data baru / update bulan yang sama
    public function store(Request $request)
    {
        $request->validate([
            'bulan'        => 'required|string',
            'emas_gram'    => 'required|numeric|min:0',
            'harga_emas'   => 'required|integer|min:0',
            'cicilan'      => 'required|integer|min:0',
            'dana_darurat' => 'nullable|integer|min:0',
            'reksa_dana'   => 'nullable|integer|min:0',
            'sbn'          => 'nullable|integer|min:0',
            'catatan'      => 'nullable|string|max:255',
        ]);

        // Update kalau bulan sama, insert kalau baru
        Portofolio::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'bulan'   => $request->bulan,
            ],
            [
                'emas_gram'    => $request->emas_gram,
                'harga_emas'   => $request->harga_emas,
                'cicilan'      => $request->cicilan,
                'dana_darurat' => $request->dana_darurat ?? 0,
                'reksa_dana'   => $request->reksa_dana ?? 0,
                'sbn'          => $request->sbn ?? 0,
                'catatan'      => $request->catatan,
            ]
        );

        return redirect()->route('dashboard')
            ->with('success', 'Data berhasil disimpan!');
    }

    // Hapus satu data
    public function destroy(Portofolio $portofolio)
    {
        // Pastikan hanya pemilik yang bisa hapus
        abort_if($portofolio->user_id !== auth()->id(), 403);
        $portofolio->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Data berhasil dihapus!');
    }

    // Edit — ambil data untuk diedit
public function edit(Portofolio $portofolio)
{
    abort_if($portofolio->user_id !== auth()->id(), 403);

    return Inertia::render('Edit', [
        'portofolio' => $portofolio,
    ]);
}

// Update — simpan perubahan
public function update(Request $request, Portofolio $portofolio)
{
    abort_if($portofolio->user_id !== auth()->id(), 403);

    $request->validate([
        'bulan'        => 'required|string',
        'emas_gram'    => 'required|numeric|min:0',
        'harga_emas'   => 'required|integer|min:0',
        'cicilan'      => 'required|integer|min:0',
        'dana_darurat' => 'nullable|integer|min:0',
        'reksa_dana'   => 'nullable|integer|min:0',
        'sbn'          => 'nullable|integer|min:0',
        'catatan'      => 'nullable|string|max:255',
    ]);

    $portofolio->update([
        'bulan'        => $request->bulan,
        'emas_gram'    => $request->emas_gram,
        'harga_emas'   => $request->harga_emas,
        'cicilan'      => $request->cicilan,
        'dana_darurat' => $request->dana_darurat ?? 0,
        'reksa_dana'   => $request->reksa_dana ?? 0,
        'sbn'          => $request->sbn ?? 0,
        'catatan'      => $request->catatan,
    ]);

    return redirect()->route('dashboard')
        ->with('success', 'Data berhasil diupdate!');
}


}
