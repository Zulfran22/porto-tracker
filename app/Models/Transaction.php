<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'recurring_transaction_id', 'tanggal', 'type', 'kategori', 'jumlah', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'jumlah'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}