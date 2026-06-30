<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'user_id', 'tanggal', 'kategori', 'jumlah', 'catatan',
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