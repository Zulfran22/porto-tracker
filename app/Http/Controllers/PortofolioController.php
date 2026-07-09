<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortofolioRequest;
use App\Models\InvestmentType;
use App\Models\KontrakCicilanEmas;
use App\Models\Portofolio;
use App\Models\Target;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PortofolioController extends Controller
{
    // Halaman dashboard
    public function index()
    {
        InvestmentType::ensureDefaultsFor(auth()->id());

        $data = Portofolio::with('items')->where('user_id', auth()->id())
            ->orderBy('bulan', 'asc')
            ->get();

        $cashflow = Transaction::where('user_id', auth()->id())
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->get();

        return Inertia::render('Dashboard', [
            'portofolios' => $data,
            'investmentTypes' => InvestmentType::where('user_id', auth()->id())->orderBy('urutan')->get(),
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
            // Notif jatuh tempo hilang begitu cicilan bulan berjalan tercatat lewat
            // halaman/modal Catat (field "Cicilan emas" pada data bulan ini) —
            // tombol "Catat pembayaran" di notif hanya mengarahkan ke form itu.
            'cicilanPaid' => (int) ($data->firstWhere('bulan', now()->format('Y-m'))?->cicilan ?? 0) > 0,
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
        InvestmentType::ensureDefaultsFor(auth()->id());

        $last = Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan', 'desc')->first();

        return Inertia::render('Catat', [
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'investmentTypes' => InvestmentType::where('user_id', auth()->id())->orderBy('urutan')->get(),
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    }

    // Halaman /grafik
    public function grafik()
    {
        $data = Portofolio::with('items')->where('user_id', auth()->id())->orderBy('bulan')->get();

        return Inertia::render('Grafik', [
            'portofolios' => $data,
            'investmentTypes' => InvestmentType::where('user_id', auth()->id())->orderBy('urutan')->get(),
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
            'investmentTypes' => InvestmentType::where('user_id', auth()->id())
                ->where('unit', 'rupiah')->orderBy('urutan')->get(),
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
            'budgetBulanan' => Target::budgetBulananUntuk(auth()->id()),
        ]);
    }

    // Data buat modal "Catat" di FAB — dipanggil dari halaman mana pun karena
    // AuthenticatedLayout tidak menerima portofolios/aktifKontrak lewat props Inertia.
    // Kalau ?id= dikirim, modal dipakai untuk edit data bulan lain (bukan bulan berjalan).
    public function catatContext(Request $request)
    {
        $userId = auth()->id();

        // Akun baru bisa membuka modal Catat (FAB) sebelum pernah mampir ke
        // dashboard — tanpa ini, investmentTypes kosong dan form-nya tak berisi
        // field apa pun.
        InvestmentType::ensureDefaultsFor($userId);

        if ($request->filled('id')) {
            $existing = Portofolio::with('items')->where('user_id', $userId)->findOrFail($request->id);
            $bulan = $existing->bulan;
        } else {
            $bulan = now()->format('Y-m');
            $existing = Portofolio::with('items')->where('user_id', $userId)->where('bulan', $bulan)->first();
        }

        $last = Portofolio::where('user_id', $userId)->orderBy('bulan', 'desc')->first();

        return response()->json([
            'bulan' => $bulan,
            'existing' => $existing,
            'lastHargaEmas' => $last ? (int) $last->harga_emas : null,
            'investmentTypes' => InvestmentType::where('user_id', $userId)->orderBy('urutan')->get(),
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
                'harga_emas' => $request->harga_emas,
                'cicilan' => $request->cicilan,
                'catatan' => $request->catatan,
            ];

            if ($portofolio) {
                if ($portofolio->trashed()) {
                    $portofolio->restore();
                }
                $portofolio->update($attributes);
            } else {
                $portofolio = Portofolio::create([
                    'user_id' => auth()->id(),
                    'bulan' => $request->bulan,
                    ...$attributes,
                ]);
            }

            $this->syncItems($portofolio, $request->input('items', []));
            $this->syncCicilanTransaction($portofolio);
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

        DB::transaction(function () use ($request, $portofolio) {
            $portofolio->update([
                'bulan' => $request->bulan,
                'harga_emas' => $request->harga_emas,
                'cicilan' => $request->cicilan,
                'catatan' => $request->catatan,
            ]);

            $this->syncItems($portofolio, $request->input('items', []));
            $this->syncCicilanTransaction($portofolio);
        });

        return redirect()->route('dashboard')
            ->with('success', 'Data berhasil diupdate!');
    }

    // Pembayaran cicilan yang dicatat di data bulanan disinkronkan sebagai
    // Transaction expense (kategori "Cicilan Emas") supaya masuk cashflow /
    // saving rate — sebelumnya pengeluaran bulanan terbesar user tidak pernah
    // terhitung kecuali dicatat manual dua kali. Idempoten per bulan: maksimal
    // satu transaksi kategori ini per bulan (di-update kalau nilai berubah,
    // dihapus kalau cicilan dikosongkan).
    private function syncCicilanTransaction(Portofolio $portofolio): void
    {
        $awalBulan = $portofolio->bulan.'-01';
        $akhirBulan = now()->parse($awalBulan)->endOfMonth()->toDateString();

        $existing = Transaction::where('user_id', $portofolio->user_id)
            ->where('type', 'expense')
            ->where('kategori', Transaction::KATEGORI_CICILAN_EMAS)
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->first();

        $cicilan = (int) ($portofolio->cicilan ?? 0);

        if ($cicilan > 0) {
            if ($existing) {
                $existing->update(['jumlah' => $cicilan]);
            } else {
                Transaction::create([
                    'user_id' => $portofolio->user_id,
                    // Bulan berjalan dicatat per hari ini; bulan lain (edit data
                    // lama) dipatok ke tanggal 1 bulan itu.
                    'tanggal' => $portofolio->bulan === now()->format('Y-m')
                        ? now()->toDateString()
                        : $awalBulan,
                    'type' => 'expense',
                    'kategori' => Transaction::KATEGORI_CICILAN_EMAS,
                    'jumlah' => $cicilan,
                    'catatan' => 'Otomatis dari data portofolio '.$portofolio->bulan,
                ]);
            }
        } elseif ($existing) {
            $existing->delete();
        }
    }

    // Full delete-then-recreate per save — jumlah item per bulan kecil (satu
    // per jenis investasi), jadi diff/upsert per baris tidak sepadan
    // kompleksitasnya. Ini juga otomatis membuang item yang dihapus user dari form.
    private function syncItems(Portofolio $portofolio, array $items): void
    {
        $portofolio->items()->delete();

        foreach ($items as $item) {
            $portofolio->items()->create([
                'type_name' => $item['type_name'],
                'unit' => $item['unit'],
                'gram' => $item['unit'] === 'gram' ? ($item['gram'] ?? 0) : null,
                'jumlah' => $item['unit'] === 'rupiah' ? ($item['jumlah'] ?? 0) : null,
            ]);
        }
    }
}
