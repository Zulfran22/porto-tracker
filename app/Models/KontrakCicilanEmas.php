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
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'tenor_bulan'     => 'integer',
        'total_gram'      => 'float',
        'angsuran_bulan'  => 'integer',
        'sewa_modal'      => 'integer',
        'biaya_admin'     => 'integer',
    ];

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
}
