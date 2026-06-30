<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Target extends Model
{
    protected $fillable = [
        'user_id',
        'target_emas',
        'target_darurat',
        'target_reksa',
    ];

    protected $casts = [
        'target_emas'    => 'float',
        'target_darurat' => 'integer',
        'target_reksa'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}