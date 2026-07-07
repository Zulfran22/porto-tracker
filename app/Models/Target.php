<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Target extends Model
{
    protected $fillable = [
        'user_id',
        'budget_bulanan',
    ];

    protected $casts = [
        'budget_bulanan' => 'integer',
    ];

    // Budget saving bulanan milik user; jatuh ke default kalau baris target belum ada.
    public static function budgetBulananUntuk(int $userId): int
    {
        return (int) (static::where('user_id', $userId)->value('budget_bulanan') ?? 3000000);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
