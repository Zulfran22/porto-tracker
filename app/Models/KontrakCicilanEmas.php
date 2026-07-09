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

    // Estimasi gram yang sudah menjadi hak user berdasarkan angsuran berjalan.
    // Aplikasi tidak mencatat pembayaran per angsuran, jadi dipakai proxy
    // jadwal: angsuran pertama dianggap dibayar saat kontrak dimulai, lalu
    // bertambah satu tiap bulan, dibatasi tenor. Dipakai oleh
    // Portofolio::getTotalAttribute dan tampilan Total emas/Target — supaya
    // total portofolio mencerminkan porsi yang benar-benar terbayar, bukan
    // seluruh gram kontrak sejak hari pertama (aset menggelembung, sisa
    // kewajiban diabaikan).
    public function getGramTerbayarAttribute(): float
    {
        if ($this->tenor_bulan <= 0 || $this->total_gram <= 0) {
            return 0.0;
        }

        $angsuranTerbayar = min(
            $this->tenor_bulan,
            max(0, (int) floor($this->tanggal_mulai->diffInMonths(now())) + 1)
        );

        return round($this->total_gram * $angsuranTerbayar / $this->tenor_bulan, 4);
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
