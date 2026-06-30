<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['user_id', 'kategori', 'limit_jumlah'];

    protected $casts = ['limit_jumlah' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
