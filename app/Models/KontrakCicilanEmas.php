<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KontrakCicilanEmas extends Model
{
    protected $table = 'kontrak_cicilan_emas';

    protected $fillable = [
        'user_id',
        'nomor_kontrak',
        'cabang',
        'no_rekening',
        'tanggal_mulai',
        'tanggal_selesai',
        'tenor_bulan',
        'total_gram',
        'angsuran_bulan',
        'sewa_modal',
        'biaya_admin',
        'status',
        'catatan',
        'file_kontrak',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tenor_bulan' => 'integer',
        'total_gram' => 'float',
        'angsuran_bulan' => 'integer',
        'sewa_modal' => 'integer',
        'biaya_admin' => 'integer',
    ];

    protected $appends = ['bep_per_gram', 'gram_terbayar', 'bulan_belum_tercatat'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function aktifUntuk(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'aktif')
            ->orderBy('tanggal_mulai', 'desc')
            ->first();
    }

    // Estimasi gram yang sudah menjadi hak user PADA bulan snapshot tertentu
    // (format Y-m) — dihitung dari NOMINAL CICILAN YANG BENAR-BENAR DICATAT
    // tiap bulan (field "Cicilan emas (Rp)" di data portofolio bulan itu),
    // dibagi harga emas bulan itu juga, dijumlah dari bulan kontrak mulai
    // sampai $bulan. Bukan proxy jadwal rata (dulu: total_gram x bulan
    // berjalan / tenor) — kalau angsuran riil beda dari jadwal (lebih/kurang
    // bayar), progress gram ikut menyesuaikan, bukan menganggap semua bulan
    // sama besar. Dibatasi maksimal total_gram kontrak. Point-in-time — bulan
    // lama dinilai dari data s.d. bulan ITU saja, tidak terpengaruh bulan
    // sesudahnya (riwayat/Grafik tidak ditulis ulang retroaktif).
    public function gramTerbayarPada(string $bulan): float
    {
        if ($this->total_gram <= 0) {
            return 0.0;
        }

        $gram = Portofolio::where('user_id', $this->user_id)
            ->where('bulan', '>=', $this->tanggal_mulai->format('Y-m'))
            ->where('bulan', '<=', $bulan)
            ->get(['cicilan', 'harga_emas'])
            ->reduce(function (float $gram, Portofolio $p) {
                $cicilan = (int) ($p->cicilan ?? 0);
                $harga = (int) ($p->harga_emas ?? 0);

                return $gram + ($cicilan > 0 && $harga > 0 ? $cicilan / $harga : 0.0);
            }, 0.0);

        return round(min($gram, $this->total_gram), 4);
    }

    public function getGramTerbayarAttribute(): float
    {
        return $this->gramTerbayarPada(now()->format('Y-m'));
    }

    // gramTerbayarPada() cuma menjumlah bulan yang ADA baris Portofolio-nya
    // — bulan yang belum sempat dicatat diam-diam dianggap 0, jadi progress
    // under-count tanpa ada tanda apa pun. Ini daftar bulan (format Y-m)
    // sejak kontrak mulai s.d. bulan LALU (bulan berjalan belum dianggap
    // terlambat) yang belum punya baris Portofolio sama sekali — dipakai
    // buat tampilkan peringatan di UI, bukan buat menebak nilainya.
    public function getBulanBelumTercatatAttribute(): array
    {
        $mulai = $this->tanggal_mulai->format('Y-m');
        $akhir = min($this->tanggal_selesai->format('Y-m'), now()->subMonth()->format('Y-m'));

        if ($akhir < $mulai) {
            return [];
        }

        $semuaBulan = [];
        $cursor = $this->tanggal_mulai->copy()->startOfMonth();
        $akhirDate = now()->parse($akhir.'-01');
        while ($cursor->lte($akhirDate)) {
            $semuaBulan[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $tercatat = Portofolio::where('user_id', $this->user_id)
            ->whereIn('bulan', $semuaBulan)
            ->pluck('bulan')
            ->all();

        return array_values(array_diff($semuaBulan, $tercatat));
    }

    // Harga breakeven per gram: total biaya kontrak (angsuran selama tenor + sewa modal + biaya admin) dibagi total gram
    public function getBepPerGramAttribute(): int
    {
        if ($this->total_gram <= 0) {
            return 0;
        }

        $totalBiaya = ($this->angsuran_bulan * $this->tenor_bulan) + ($this->sewa_modal ?? 0) + ($this->biaya_admin ?? 0);

        return (int) round($totalBiaya / $this->total_gram);
    }
}
