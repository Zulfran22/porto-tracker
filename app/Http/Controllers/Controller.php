<?php

namespace App\Http\Controllers;
abstract class Controller
{
    //
}   

use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::where('user_id', auth()->id())
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Pengeluaran', [
            'expenses' => $expenses,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'  => 'required|date',
            'kategori' => 'required|string|max:50',
            'jumlah'   => 'required|integer|min:0',
            'catatan'  => 'nullable|string|max:255',
        ]);

        Expense::create([
            'user_id'  => auth()->id(),
            'tanggal'  => $request->tanggal,
            'kategori' => $request->kategori,
            'jumlah'   => $request->jumlah,
            'catatan'  => $request->catatan,
        ]);

        return back()->with('success', 'Pengeluaran berhasil dicatat!');
    }

    public function destroy(Expense $expense)
    {
        abort_if($expense->user_id !== auth()->id(), 403);
        $expense->delete();

        return back()->with('success', 'Pengeluaran berhasil dihapus!');
    }
}