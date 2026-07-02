<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Finance Defaults
    |--------------------------------------------------------------------------
    |
    | Dipakai saat user belum punya kontrak cicilan emas aktif tercatat.
    | Nilai ini disinkronkan manual dengan CICILAN_GRAM di
    | resources/js/Composables/useFinanceConstants.js.
    |
    */

    'cicilan_gram_fallback' => (float) env('FINANCE_CICILAN_GRAM_FALLBACK', 5),

];
