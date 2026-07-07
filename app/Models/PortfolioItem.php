<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioItem extends Model
{
    protected $fillable = ['portofolio_id', 'type_name', 'unit', 'gram', 'jumlah'];

    protected $casts = [
        'gram' => 'float',
        'jumlah' => 'integer',
    ];

    public function portofolio(): BelongsTo
    {
        return $this->belongsTo(Portofolio::class);
    }
}
