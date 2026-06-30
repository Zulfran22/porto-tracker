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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Hitung total nilai portofolio
    public function getTotalAttribute(): int
    {
        $nilaiEmasTunai = $this->emas_gram * $this->harga_emas;
        $nilaiCicilan = 5 * $this->harga_emas; // 5 gram cicilan
        return $nilaiEmasTunai + $nilaiCicilan + 
               $this->dana_darurat + $this->reksa_dana + $this->sbn;
    }
}