<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentType extends Model
{
    protected $fillable = ['user_id', 'name', 'unit', 'is_default', 'urutan'];

    protected $casts = [
        'is_default' => 'boolean',
        'urutan' => 'integer',
    ];

    public const DEFAULTS = [
        ['name' => 'Emas Tunai',   'unit' => 'gram',   'is_default' => true, 'urutan' => 1],
        ['name' => 'Dana Darurat', 'unit' => 'rupiah', 'is_default' => true, 'urutan' => 2],
        ['name' => 'Reksa Dana',   'unit' => 'rupiah', 'is_default' => true, 'urutan' => 3],
        ['name' => 'SBN',          'unit' => 'rupiah', 'is_default' => true, 'urutan' => 4],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Lazily seeds the 4 baseline types the first time a user touches any
    // portofolio/target page — mirrors TargetController's existing
    // createDefaultTarget() lazy pattern, decoupled from registration so it
    // works uniformly regardless of when the account was created.
    public static function ensureDefaultsFor(int $userId): void
    {
        if (static::where('user_id', $userId)->exists()) {
            return;
        }
        foreach (static::DEFAULTS as $default) {
            static::create(['user_id' => $userId, ...$default]);
        }
    }
}
