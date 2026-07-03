<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortofolioRequest;
use App\Models\KontrakCicilanEmas;
use App\Models\Portofolio;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
            'cashflow' => [
                'income' => $cashflow->where('type', 'income')->sum('jumlah'),
                'expense' => $cashflow->where('type', 'expense')->sum('jumlah'),
                'net' => $cashflow->where('type', 'income')->sum('jumlah')
                           - $cashflow->where('type', 'expense')->sum('jumlah'),
            ],
        ]);
    }

    // Halaman /catat (form penuh, terpisah dari modal cepat di FAB)
    public function create()
    {
        $last = Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan', 'desc')->first();

        return Inertia::render('Catat', [
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    }

    // Halaman /grafik
    public function grafik()
    {
        $data = Portofolio::where('user_id', auth()->id())->orderBy('bulan')->get();

        return Inertia::render('Grafik', [
            'portofolios' => $data,
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    }

    // Halaman /info
    public function info()
    {
        $last = Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan', 'desc')->first();

        return Inertia::render('Info', [
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'lastCicilan' => $last ? (int) $last->cicilan : null,
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    }

    // Data buat modal "Catat" di FAB — dipanggil dari halaman mana pun karena
    // AuthenticatedLayout tidak menerima portofolios/aktifKontrak lewat props Inertia.
    // Kalau ?id= dikirim, modal dipakai untuk edit data bulan lain (bukan bulan berjalan).
    public function catatContext(Request $request)
    {
        $userId = auth()->id();

        if ($request->filled('id')) {
            $existing = Portofolio::where('user_id', $userId)->findOrFail($request->id);
            $bulan = $existing->bulan;
        } else {
            $bulan = now()->format('Y-m');
            $existing = Portofolio::where('user_id', $userId)->where('bulan', $bulan)->first();
        }

        $last = Portofolio::where('user_id', $userId)->orderBy('bulan', 'desc')->first();

        return response()->json([
            'bulan' => $bulan,
            'existing' => $existing,
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk($userId),
        ]);
    }

    // Simpan data baru / update bulan yang sama. Dicari termasuk yang sudah
    // soft-deleted supaya "catat ulang" bulan yang pernah dihapus me-restore
    // baris lama, bukan bentrok dengan unique(user_id,bulan) di DB.
    public function store(PortofolioRequest $request)
    {
        DB::transaction(function () use ($request) {
            $portofolio = Portofolio::withTrashed()
                ->where('user_id', auth()->id())
                ->where('bulan', $request->bulan)
                ->first();

            $attributes = [
                'emas_gram' => $request->emas_gram,
                'harga_emas' => $request->harga_emas,
                'cicilan' => $request->cicilan,
                'dana_darurat' => $request->dana_darurat ?? 0,
                'reksa_dana' => $request->reksa_dana ?? 0,
                'sbn' => $request->sbn ?? 0,
                'catatan' => $request->catatan,
            ];

            if ($portofolio) {
                if ($portofolio->trashed()) {
                    $portofolio->restore();
                }
                $portofolio->update($attributes);
            } else {
                Portofolio::create([
                    'user_id' => auth()->id(),
                    'bulan' => $request->bulan,
                    ...$attributes,
                ]);
            }
        });

        return redirect()->route('dashboard')
            ->with('success', 'Data berhasil disimpan!');
    }

    // Hapus satu data
    public function destroy(Portofolio $portofolio)
    {
        $this->authorize('delete', $portofolio);
        $portofolio->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Data berhasil dihapus!');
    }

    // Update — simpan perubahan
    public function update(PortofolioRequest $request, Portofolio $portofolio)
    {
        $this->authorize('update', $portofolio);

        $portofolio->update([
            'bulan' => $request->bulan,
            'emas_gram' => $request->emas_gram,
            'harga_emas' => $request->harga_emas,
            'cicilan' => $request->cicilan,
            'dana_darurat' => $request->dana_darurat ?? 0,
            'reksa_dana' => $request->reksa_dana ?? 0,
            'sbn' => $request->sbn ?? 0,
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Data berhasil diupdate!');
    }
}
