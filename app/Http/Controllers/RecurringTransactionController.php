<?php

namespace App\Http\Controllers;

use App\Actions\ApplyRecurringTransactions;
use App\Http\Requests\RecurringTransactionRequest;
use App\Models\RecurringTransaction;

class RecurringTransactionController extends Controller
{
    public function store(RecurringTransactionRequest $request)
    {
        RecurringTransaction::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'kategori' => $request->kategori,
            'jumlah' => $request->jumlah,
            'catatan' => $request->catatan,
        ]);

        return back()->with('success', 'Transaksi berulang ditambahkan!');
    }

    public function toggle(RecurringTransaction $recurring)
    {
        $this->authorize('update', $recurring);
        $recurring->update(['aktif' => ! $recurring->aktif]);

        return back();
    }

    public function destroy(RecurringTransaction $recurring)
    {
        $this->authorize('delete', $recurring);
        $recurring->delete();

        return back()->with('success', 'Dihapus!');
    }

    // Terapkan semua recurring yang aktif ke hari ini (idempoten — lewati yang sudah diterapkan hari ini)
    public function apply(ApplyRecurringTransactions $action)
    {
        $result = $action->execute(auth()->id());

        $pesan = "{$result['applied']} transaksi berulang diterapkan!";
        if ($result['skipped'] > 0) {
            $pesan .= " ({$result['skipped']} sudah diterapkan hari ini, dilewati)";
        }

        return back()->with('success', $pesan);
    }
}
