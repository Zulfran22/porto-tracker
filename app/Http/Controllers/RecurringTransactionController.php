<?php

namespace App\Http\Controllers;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type'     => 'required|in:income,expense',
            'kategori' => 'required|string|max:50',
            'jumlah'   => 'required|integer|min:1',
            'catatan'  => 'nullable|string|max:255',
        ]);

        RecurringTransaction::create([
            'user_id'  => auth()->id(),
            'type'     => $request->type,
            'kategori' => $request->kategori,
            'jumlah'   => $request->jumlah,
            'catatan'  => $request->catatan,
        ]);

        return back()->with('success', 'Transaksi berulang ditambahkan!');
    }

    public function toggle(RecurringTransaction $recurring)
    {
        abort_if($recurring->user_id !== auth()->id(), 403);
        $recurring->update(['aktif' => !$recurring->aktif]);
        return back();
    }

    public function destroy(RecurringTransaction $recurring)
    {
        abort_if($recurring->user_id !== auth()->id(), 403);
        $recurring->delete();
        return back()->with('success', 'Dihapus!');
    }

    // Terapkan semua recurring yang aktif ke hari ini
    public function apply(Request $request)
    {
        $today = now()->toDateString();

        $recurrings = RecurringTransaction::where('user_id', auth()->id())
            ->where('aktif', true)
            ->get();

        $count = 0;
        foreach ($recurrings as $r) {
            Transaction::create([
                'user_id'  => auth()->id(),
                'tanggal'  => $today,
                'type'     => $r->type,
                'kategori' => $r->kategori,
                'jumlah'   => $r->jumlah,
                'catatan'  => $r->catatan ?? 'Transaksi berulang',
            ]);
            $count++;
        }

        return back()->with('success', "{$count} transaksi berulang diterapkan!");
    }
}
