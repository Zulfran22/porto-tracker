<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    // Kategori transaksi yang disinkronkan otomatis dari field "cicilan" pada
    // data portofolio bulanan (PortofolioController::syncCicilanTransaction) —
    // supaya pembayaran cicilan ikut terhitung di cashflow/saving rate tanpa
    // pencatatan dobel manual.
    public const KATEGORI_CICILAN_EMAS = 'Cicilan Emas';

    protected $fillable = [
        'user_id', 'recurring_transaction_id', 'tanggal', 'type', 'kategori', 'jumlah', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'jumlah' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
