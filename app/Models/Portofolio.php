<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portofolio extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'bulan',
        'harga_emas',
        'cicilan',
        'catatan',
    ];

    protected $casts = [
        'harga_emas' => 'integer',
        'cicilan' => 'integer',
    ];

    protected $appends = ['total', 'gram_cicilan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    // Hitung total nilai portofolio. Query KontrakCicilanEmas langsung tiap panggilan
    // (bukan static cache per user_id) — jumlah baris per user kecil (bulanan), dan
    // static property pada model tidak direset antar request di Octane/queue worker,
    // yang bisa membocorkan kontrak milik user lain kalau user_id dipakai ulang.
    //
    // Nilai investasi datang dari relasi items() — controller WAJIB eager-load
    // with('items') tiap kali men-serialize Portofolio, kalau tidak setiap baris
    // memicu query N+1 lewat append 'total' ini.
    // Gram kontrak yang dihitung aset PADA bulan snapshot ini (point-in-time,
    // 0 untuk bulan sebelum kontrak) — dipakai getTotalAttribute dan juga
    // frontend (Total emas/Alokasi/Grafik) supaya konsisten dengan total.
    public function getGramCicilanAttribute(): float
    {
        $kontrakAktif = KontrakCicilanEmas::aktifUntuk($this->user_id);

        return $kontrakAktif ? $kontrakAktif->gramTerbayarPada($this->bulan) : 0.0;
    }

    public function getTotalAttribute(): int
    {
        $hargaEmas = (int) ($this->harga_emas ?? 0);

        $nilaiCicilan = $this->gram_cicilan * $hargaEmas;

        $nilaiItems = $this->items->sum(function (PortfolioItem $item) use ($hargaEmas) {
            return $item->unit === 'gram'
                ? (float) $item->gram * $hargaEmas
                : (float) ($item->jumlah ?? 0);
        });

        return (int) round($nilaiCicilan + $nilaiItems);
    }
}
