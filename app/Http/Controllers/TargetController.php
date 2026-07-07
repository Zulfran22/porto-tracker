<?php

namespace App\Http\Controllers;

use App\Models\InvestmentTarget;
use App\Models\InvestmentType;
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
        InvestmentType::ensureDefaultsFor(auth()->id());

        $portofolios = Portofolio::with('items')->where('user_id', auth()->id())
            ->orderBy('bulan')->get();

        $target = $this->userTarget() ?? $this->createDefaultTarget();

        return Inertia::render('Target', [
            'portofolios' => $portofolios,
            'investmentTypes' => InvestmentType::where('user_id', auth()->id())->orderBy('urutan')->get(),
            'investmentTargets' => InvestmentTarget::where('user_id', auth()->id())->get(),
            'target' => $target,
            'aktifKontrak' => KontrakCicilanEmas::aktifUntuk(auth()->id()),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'targets' => ['required', 'array'],
            'targets.*.type_name' => ['required', 'string', 'max:50'],
            'targets.*.target_value' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($data['targets'] as $t) {
            InvestmentTarget::updateOrCreate(
                ['user_id' => auth()->id(), 'type_name' => $t['type_name']],
                ['target_value' => $t['target_value']]
            );
        }

        return back()->with('success', 'Target berhasil disimpan!');
    }

    // Dipanggil dari slider "Budget/bln" di Dashboard (debounced) — tanpa flash
    // supaya tidak memunculkan banner sukses setiap kali slider digeser.
    public function updateBudget(Request $request)
    {
        $request->validate([
            'budget_bulanan' => 'required|integer|min:1000000|max:100000000',
        ]);

        $target = $this->userTarget();
        if ($target) {
            $target->update(['budget_bulanan' => $request->budget_bulanan]);
        } else {
            $this->createDefaultTarget(['budget_bulanan' => $request->budget_bulanan]);
        }

        return back();
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
            ], $attributes));
        } catch (UniqueConstraintViolationException) {
            return $this->userTarget();
        }
    }
}
