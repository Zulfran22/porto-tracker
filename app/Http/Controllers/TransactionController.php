<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetRequest;
use App\Http\Requests\TransactionRequest;
use App\Models\Budget;
use App\Models\CustomCategory;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Inertia\Inertia;

class TransactionController extends Controller
{
    // Riwayat, trend chart (6 bulan), dan CSV export di Keuangan.vue semuanya
    // dihitung dari koleksi ini di sisi klien, jadi tidak dipotong ke jendela
    // waktu tertentu — tapi dibatasi jumlah baris sebagai pagar pengaman
    // supaya query & payload halaman tidak tumbuh tanpa batas selamanya.
    // Kalau riwayat tahunan sungguhan dibutuhkan nanti, ini perlu diganti
    // pagination + endpoint export CSV sendiri di sisi server.
    private const MAX_TRANSACTIONS = 2000;

    public function index()
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->limit(self::MAX_TRANSACTIONS)
            ->get();

        $budgets = Budget::where('user_id', auth()->id())->get();
        $recurrings = RecurringTransaction::where('user_id', auth()->id())->orderBy('type')->get();
        $customCats = CustomCategory::where('user_id', auth()->id())->get();

        return Inertia::render('Keuangan', [
            'transactions' => $transactions,
            'budgets' => $budgets,
            'recurrings' => $recurrings,
            'customCats' => $customCats,
        ]);
    }

    public function store(TransactionRequest $request)
    {
        Transaction::create([
            'user_id' => auth()->id(),
            'tanggal' => $request->tanggal,
            'type' => $request->type,
            'kategori' => $request->kategori,
            'jumlah' => $request->jumlah,
            'catatan' => $request->catatan,
        ]);

        return back()->with('success', 'Transaksi berhasil dicatat!');
    }

    public function storeBudget(BudgetRequest $request)
    {
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
        $this->authorize('delete', $transaction);
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus!');
    }
}
