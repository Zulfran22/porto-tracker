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

    protected $appends = ['bep_per_gram'];

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
