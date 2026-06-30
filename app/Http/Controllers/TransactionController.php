<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $budgets = Budget::where('user_id', auth()->id())->get();

        return Inertia::render('Keuangan', [
            'transactions' => $transactions,
            'budgets'      => $budgets,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'  => 'required|date',
            'type'     => 'required|in:income,expense',
            'kategori' => 'required|string|max:50',
            'jumlah'   => 'required|integer|min:0',
            'catatan'  => 'nullable|string|max:255',
        ]);

        Transaction::create([
            'user_id'  => auth()->id(),
            'tanggal'  => $request->tanggal,
            'type'     => $request->type,
            'kategori' => $request->kategori,
            'jumlah'   => $request->jumlah,
            'catatan'  => $request->catatan,
        ]);

        return back()->with('success', 'Transaksi berhasil dicatat!');
    }

    public function storeBudget(Request $request)
    {
        $request->validate([
            'kategori'     => 'required|string|max:50',
            'limit_jumlah' => 'required|integer|min:0',
        ]);

        Budget::updateOrCreate(
            ['user_id' => auth()->id(), 'kategori' => $request->kategori],
            ['limit_jumlah' => $request->limit_jumlah]
        );

        return back()->with('success', 'Budget disimpan!');
    }

    public function destroyBudget(string $kategori)
    {
        Budget::where('user_id', auth()->id())
            ->where('kategori', $kategori)
            ->delete();

        return back()->with('success', 'Budget berhasil dihapus!');
    }

    public function destroy(Transaction $transaction)
    {
        abort_if($transaction->user_id !== auth()->id(), 403);
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus!');
    }
}
