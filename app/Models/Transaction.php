<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

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
