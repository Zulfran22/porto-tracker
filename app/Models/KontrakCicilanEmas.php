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

    protected $appends = ['bep_per_gram', 'gram_terbayar'];

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
    // (format Y-m). Aplikasi tidak mencatat pembayaran per angsuran, jadi
    // dipakai proxy jadwal: angsuran pertama dianggap dibayar di bulan kontrak
    // dimulai, bertambah satu tiap bulan, dibatasi tenor. Point-in-time —
    // bulan sebelum kontrak menghasilkan 0, dan bulan lama dinilai dengan
    // angsuran yang sudah berjalan pada bulan ITU, bukan kondisi hari ini
    // (dulu seluruh riwayat/Grafik ditulis ulang tiap angsuran bertambah).
    public function gramTerbayarPada(string $bulan): float
    {
        if ($this->tenor_bulan <= 0 || $this->total_gram <= 0) {
            return 0.0;
        }

        $selisihBulan = (int) floor(
            $this->tanggal_mulai->copy()->startOfMonth()->diffInMonths(now()->parse($bulan.'-01'))
        );

        if ($selisihBulan < 0) {
            return 0.0;
        }

        $angsuranTerbayar = min($this->tenor_bulan, $selisihBulan + 1);

        return round($this->total_gram * $angsuranTerbayar / $this->tenor_bulan, 4);
    }

    public function getGramTerbayarAttribute(): float
    {
        return $this->gramTerbayarPada(now()->format('Y-m'));
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
