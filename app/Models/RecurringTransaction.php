<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringTransaction extends Model
{
    protected $fillable = ['user_id', 'type', 'kategori', 'jumlah', 'catatan', 'aktif'];

    protected $casts = [
        'jumlah' => 'integer',
        'aktif'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
