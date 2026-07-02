<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portofolio extends Model
{
    protected $fillable = [
        'user_id',
        'bulan',
        'emas_gram',
        'harga_emas',
        'cicilan',
        'dana_darurat',
        'reksa_dana',
        'sbn',
        'catatan',
    ];

    protected $casts = [
        'emas_gram' => 'float',
        'harga_emas' => 'integer',
        'cicilan' => 'integer',
        'dana_darurat' => 'integer',
        'reksa_dana' => 'integer',
        'sbn' => 'integer',
    ];

    protected $appends = ['total'];

    // Cache kontrak aktif per user_id supaya serialisasi satu koleksi Portofolio
    // tidak query KontrakCicilanEmas berulang kali (N+1).
    protected static array $kontrakAktifCache = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Hitung total nilai portofolio
    public function getTotalAttribute(): int
    {
        if (!array_key_exists($this->user_id, static::$kontrakAktifCache)) {
            static::$kontrakAktifCache[$this->user_id] = KontrakCicilanEmas::aktifUntuk($this->user_id);
        }
        $kontrakAktif = static::$kontrakAktifCache[$this->user_id];

        $gramCicilan = $kontrakAktif
            ? (float) $kontrakAktif->total_gram
            : config('finance.cicilan_gram_fallback');

        $nilaiEmasTunai = $this->emas_gram * $this->harga_emas;
        $nilaiCicilan   = $gramCicilan * $this->harga_emas;

        return $nilaiEmasTunai + $nilaiCicilan +
               $this->dana_darurat + $this->reksa_dana + $this->sbn;
    }
}