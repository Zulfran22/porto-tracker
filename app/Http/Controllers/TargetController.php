<?php

namespace App\Http\Controllers;

use App\Models\KontrakCicilanEmas;
use App\Models\Portofolio;
use App\Models\Target;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TargetController extends Controller
{
    public function index()
    {
        $portofolios = Portofolio::where('user_id', auth()->id())
            ->orderBy('bulan')->get();

        $target = $this->userTarget() ?? $this->createDefaultTarget();

        return Inertia::render('Target', [
            'portofolios' => $portofolios,
            'target' => $target,
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'target_emas' => 'required|numeric|min:1',
            'target_darurat' => 'required|integer|min:1000000',
            'target_reksa' => 'required|integer|min:1000000',
        ]);

        $attributes = [
            'target_emas' => $request->target_emas,
            'target_darurat' => $request->target_darurat,
            'target_reksa' => $request->target_reksa,
        ];

        $target = $this->userTarget();
        if ($target) {
            $target->update($attributes);
        } else {
            $this->createDefaultTarget($attributes);
        }

        return back()->with('success', 'Target berhasil disimpan!');
    }

    private function userTarget(): ?Target
    {
        return Target::where('user_id', auth()->id())->first();
    }

    // unique(user_id) makes a genuine concurrent double-submit throw here
    // instead of silently creating two rows — caught and treated as "someone
    // else's request already created it", which is the correct outcome.
    private function createDefaultTarget(array $attributes = []): Target
    {
        try {
            return Target::create(array_merge([
                'user_id' => auth()->id(),
                'target_emas' => 10,
                'target_darurat' => 18000000,
                'target_reksa' => 50000000,
            ], $attributes));
        } catch (UniqueConstraintViolationException) {
            return $this->userTarget();
        }
    }
}
