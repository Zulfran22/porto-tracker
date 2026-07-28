<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoldPriceController extends Controller
{
    // Sumber utama: endpoint publik yang dipakai halaman pegadaian.co.id
    // sendiri buat menampilkan harga Tabungan Emas hari ini — harga RIIL,
    // bukan estimasi dari spot + markup tebakan (markup tebakan dulu bisa
    // meleset puluhan persen dari harga asli, sudah dikonfirmasi user).
    // Di-cache 5 menit (dibagi semua user); kegagalan sengaja TIDAK
    // di-cache supaya request berikutnya langsung coba lagi, bukan
    // terjebak nge-serve error selama 5 menit.
    public function index()
    {
        $cached = Cache::get('gold-price');
        if ($cached) {
            return response()->json($cached);
        }

        try {
            $payload = $this->fetchPegadaian();
        } catch (\Throwable $e) {
            report($e);

            try {
                $payload = $this->fetchSpotEstimate();
            } catch (\Throwable $e2) {
                report($e2);

                return response()->json(['success' => false, 'message' => 'Gagal mengambil harga emas terbaru.'], 502);
            }
        }

        Cache::put('gold-price', $payload, now()->addMinutes(5));

        return response()->json($payload);
    }

    // hargaJual pada response Pegadaian adalah harga JUAL PEGADAIAN KE
    // CUSTOMER — artinya harga yang customer BAYAR buat BELI emas (ini yang
    // dipakai isi field "Harga emas" pas mencatat pembelian). hargaBeli
    // adalah harga BUYBACK (Pegadaian beli balik dari customer, alias harga
    // JUAL customer) — penamaan API dari sudut pandang Pegadaian, terbalik
    // dari sudut pandang customer. unit di response per 0,01 gram.
    private function fetchPegadaian(): array
    {
        $res = Http::timeout(5)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get('https://pegadaian.co.id/gold/prices');

        $data = $res->json()['data'];
        $unit = (float) $data['unit'];

        return [
            'success' => true,
            'source' => 'pegadaian',
            'tanggal' => $data['tglBerlaku'],
            'harga_jual' => (int) round($data['hargaJual'] / $unit),
            'harga_beli' => (int) round($data['hargaBeli'] / $unit),
        ];
    }

    // Fallback kalau endpoint Pegadaian gagal/berubah — estimasi kasar dari
    // spot XAU/USD + markup (config('gold.pegadaian_markup')), TIDAK
    // seakurat sumber utama tapi tetap ada angka daripada gagal total.
    private function fetchSpotEstimate(): array
    {
        $kurs = Http::timeout(5)->get('https://api.frankfurter.app/latest?from=USD&to=IDR');
        $usdToIdr = $kurs->json()['rates']['IDR'];

        $xau = Http::timeout(5)->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get('https://data-asg.goldprice.org/dbXRates/USD');
        $xauUsd = $xau->json()['items'][0]['xauPrice'] ?? config('gold.fallback_xau_price');

        $markup = config('gold.pegadaian_markup');
        $perGramUsd = $xauUsd / 31.1035;
        $perGramIdr = round($perGramUsd * $usdToIdr);
        $pegadaian = round($perGramIdr * $markup);

        return [
            'success' => true,
            'source' => 'estimasi_spot',
            'harga_jual' => (int) $pegadaian,
            'harga_beli' => null,
        ];
    }
}
