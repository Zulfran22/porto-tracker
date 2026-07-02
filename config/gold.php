<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gold Price Settings
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk proxy harga emas (routes/web.php: /api/harga-emas).
    |
    */

    // Markup harga Pegadaian di atas harga spot (1.04 = +4%)
    'pegadaian_markup' => (float) env('GOLD_PEGADAIAN_MARKUP', 1.04),

    // Fallback harga XAU/USD (per troy ounce) bila API goldprice.org gagal
    'fallback_xau_price' => (float) env('GOLD_FALLBACK_XAU_PRICE', 3280),

];
