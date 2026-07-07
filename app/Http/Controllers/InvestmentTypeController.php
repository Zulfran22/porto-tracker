<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestmentTypeRequest;
use App\Models\InvestmentType;

class InvestmentTypeController extends Controller
{
    public function store(InvestmentTypeRequest $request)
    {
        InvestmentType::firstOrCreate(
            ['user_id' => auth()->id(), 'name' => $request->name],
            [
                'unit' => 'rupiah',
                'is_default' => false,
                'urutan' => (InvestmentType::where('user_id', auth()->id())->max('urutan') ?? 0) + 1,
            ]
        );

        return back()->with('success', 'Jenis investasi ditambahkan!');
    }

    public function destroy(InvestmentType $type)
    {
        $this->authorize('delete', $type);
        $type->delete();

        return back()->with('success', 'Jenis investasi dihapus!');
    }
}
