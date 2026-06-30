<?php

namespace App\Http\Controllers;

use App\Models\Target;
use App\Models\Portofolio;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TargetController extends Controller
{
    public function index()
    {
        $portofolios = Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan')->get();

        // Ambil target user, kalau belum ada buat default
        $target = Target::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'target_emas'    => 10,
                'target_darurat' => 18000000,
                'target_reksa'   => 50000000,
            ]
        );

        return Inertia::render('Target', [
            'portofolios' => $portofolios,
            'target'      => $target,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'target_emas'    => 'required|numeric|min:1',
            'target_darurat' => 'required|integer|min:1000000',
            'target_reksa'   => 'required|integer|min:1000000',
        ]);

        Target::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'target_emas'    => $request->target_emas,
                'target_darurat' => $request->target_darurat,
                'target_reksa'   => $request->target_reksa,
            ]
        );

        return back()->with('success', 'Target berhasil disimpan!');
    }
}