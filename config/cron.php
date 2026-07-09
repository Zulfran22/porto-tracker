<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cron Webhook Token
    |--------------------------------------------------------------------------
    |
    | Rahasia untuk endpoint /cron/recurring-apply (routes/web.php). Di host
    | free-tier yang scale-to-zero (Koyeb) proses schedule:work ikut tidur
    | bersama container, jadi job harian recurring:apply dipicu dari luar oleh
    | cron-job.org yang memanggil endpoint itu dengan token ini. Kosong =
    | endpoint mati total (selalu 403).
    |
    */

    'token' => env('CRON_TOKEN'),

];
