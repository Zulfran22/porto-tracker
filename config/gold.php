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

    // Markup harga Pegadaian di atas harga spot. Direvisi 28 Juli 2026 dari
    // 1.04 (+4%) ke 1.30 (+30%) — dicek langsung ke harga Pegadaian riil
    // hari itu (Antam/UBS/Galeri24 ~Rp2,4-2,7jt/gram vs spot+4% cuma
    // ~Rp1,98jt, beda ~30%). Premium riil Pegadaian bergerak dari hari ke
    // hari, jadi angka ini tetap perkiraan kasar, bukan patokan presisi —
    // user tetap perlu cek/koreksi manual sewaktu-waktu.
    'pegadaian_markup' => (float) env('GOLD_PEGADAIAN_MARKUP', 1.30),

    // Fallback harga XAU/USD (per troy ounce) bila API goldprice.org gagal.
    'fallback_xau_price' => (float) env('GOLD_FALLBACK_XAU_PRICE', 3280),

];
