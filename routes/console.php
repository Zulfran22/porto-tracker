<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sebelumnya "Terapkan Bulan Ini" di Keuangan.vue murni manual — kalau lupa
// diklik, transaksi berulang hari itu tidak pernah dibuat (tidak ada backfill).
// Dijadwalkan tiap hari jam 00:05 supaya jalan otomatis; tombol manual tetap
// ada untuk apply on-demand / catch-up.
Schedule::command('recurring:apply')->dailyAt('00:05');
