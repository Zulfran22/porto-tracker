<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontrakCicilanRequest;
use App\Models\KontrakCicilanEmas;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KontrakCicilanController extends Controller
{
    public function index()
    {
        $kontrak = KontrakCicilanEmas::where('user_id', auth()->id())
            ->orderBy('tanggal_mulai', 'desc')
            ->get();

        return Inertia::render('KontrakCicilan', [
            'kontrak' => $kontrak,
        ]);
    }

    public function store(KontrakCicilanRequest $request)
    {
        $path = $request->file('file_kontrak')?->store('kontrak', 'public');

        KontrakCicilanEmas::create([
            'user_id' => auth()->id(),
            'nomor_kontrak' => $request->nomor_kontrak,
            'cabang' => $request->cabang,
            'no_rekening' => $request->no_rekening,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => now()->parse($request->tanggal_mulai)->addMonths((int) $request->tenor_bulan),
            'tenor_bulan' => $request->tenor_bulan,
            'total_gram' => $request->total_gram,
            'angsuran_bulan' => $request->angsuran_bulan,
            'sewa_modal' => $request->sewa_modal,
            'biaya_admin' => $request->biaya_admin,
            'catatan' => $request->catatan,
            'file_kontrak' => $path,
        ]);

        return redirect()->route('kontrak-cicilan.index')
            ->with('success', 'Kontrak cicilan emas berhasil disimpan!');
    }

    public function update(KontrakCicilanRequest $request, KontrakCicilanEmas $kontrak)
    {
        $this->authorize('update', $kontrak);

        $path = $kontrak->file_kontrak;
        if ($request->hasFile('file_kontrak')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('file_kontrak')->store('kontrak', 'public');
        }

        $kontrak->update([
            'nomor_kontrak' => $request->nomor_kontrak,
            'cabang' => $request->cabang,
            'no_rekening' => $request->no_rekening,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => now()->parse($request->tanggal_mulai)->addMonths((int) $request->tenor_bulan),
            'tenor_bulan' => $request->tenor_bulan,
            'total_gram' => $request->total_gram,
            'angsuran_bulan' => $request->angsuran_bulan,
            'sewa_modal' => $request->sewa_modal,
            'biaya_admin' => $request->biaya_admin,
            'status' => $request->status,
            'catatan' => $request->catatan,
            'file_kontrak' => $path,
        ]);

        return redirect()->route('kontrak-cicilan.index')
            ->with('success', 'Kontrak berhasil diupdate!');
    }

    public function destroy(KontrakCicilanEmas $kontrak)
    {
        $this->authorize('delete', $kontrak);

        if ($kontrak->file_kontrak) {
            Storage::disk('public')->delete($kontrak->file_kontrak);
        }

        $kontrak->delete();

        return back()->with('success', 'Kontrak berhasil dihapus!');
    }
}
