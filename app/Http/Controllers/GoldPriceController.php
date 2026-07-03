<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoldPriceController extends Controller
{
    // Proxy harga emas — dua panggilan API eksternal live per request tanpa
    // cache berarti setiap load dashboard/catat bergantung pada latensi &
    // ketersediaan dua layanan pihak ketiga. Di-cache 5 menit; kegagalan
    // sengaja TIDAK di-cache supaya request berikutnya langsung coba lagi.
    public function index()
    {
        $cached = Cache::get('gold-price');
        if ($cached) {
            return response()->json($cached);
        }

        try {
            $kurs = Http::timeout(5)->get('https://api.frankfurter.app/latest?from=USD&to=IDR');
            $usdToIdr = $kurs->json()['rates']['IDR'];

            $xau = Http::timeout(5)->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://data-asg.goldprice.org/dbXRates/USD');
            $xauUsd = $xau->json()['items'][0]['xauPrice'] ?? config('gold.fallback_xau_price');

            $markup = config('gold.pegadaian_markup');
            $perGramUsd = $xauUsd / 31.1035;
            $perGramIdr = round($perGramUsd * $usdToIdr);
            $pegadaian = round($perGramIdr * $markup);

            $payload = [
                'success' => true,
                'xau_usd' => round($xauUsd, 2),
                'usd_idr' => round($usdToIdr),
                'spot_idr' => $perGramIdr,
                'pegadaian' => $pegadaian,
                'markup_percent' => round(($markup - 1) * 100),
            ];

            Cache::put('gold-price', $payload, now()->addMinutes(5));

            return response()->json($payload);
        } catch (\Throwable $e) {
            // \Throwable (not \Exception) — a malformed upstream response
            // (e.g. a missing array key on a null body) raises a \TypeError,
            // which \Exception alone would not catch, surfacing as an
            // uncaught 500 instead of this intended graceful fallback.
            report($e);

            return response()->json(['success' => false, 'message' => 'Gagal mengambil harga emas terbaru.'], 502);
        }
    }
}
