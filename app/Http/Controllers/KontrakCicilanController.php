<?php

namespace App\Http\Controllers;

use App\Models\KontrakCicilanEmas;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $request->validate([
            'nomor_kontrak'   => 'required|string|max:100',
            'cabang'          => 'nullable|string|max:100',
            'no_rekening'     => 'nullable|string|max:50',
            'tanggal_mulai'   => 'required|date',
            'tenor_bulan'     => 'required|integer|min:1|max:60',
            'total_gram'      => 'required|numeric|min:0',
            'angsuran_bulan'  => 'required|integer|min:0',
            'sewa_modal'      => 'nullable|integer|min:0',
            'biaya_admin'     => 'nullable|integer|min:0',
            'catatan'         => 'nullable|string|max:255',
            'file_kontrak'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('file_kontrak')?->store('kontrak', 'public');

        KontrakCicilanEmas::create([
            'user_id'         => auth()->id(),
            'nomor_kontrak'   => $request->nomor_kontrak,
            'cabang'          => $request->cabang,
            'no_rekening'     => $request->no_rekening,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => now()->parse($request->tanggal_mulai)->addMonths((int) $request->tenor_bulan),
            'tenor_bulan'     => $request->tenor_bulan,
            'total_gram'      => $request->total_gram,
            'angsuran_bulan'  => $request->angsuran_bulan,
            'sewa_modal'      => $request->sewa_modal,
            'biaya_admin'     => $request->biaya_admin,
            'catatan'         => $request->catatan,
            'file_kontrak'    => $path,
        ]);

        return redirect()->route('kontrak-cicilan.index')
            ->with('success', 'Kontrak cicilan emas berhasil disimpan!');
    }

    public function update(Request $request, KontrakCicilanEmas $kontrak)
    {
        abort_if($kontrak->user_id !== auth()->id(), 403);

        $request->validate([
            'nomor_kontrak'   => 'required|string|max:100',
            'cabang'          => 'nullable|string|max:100',
            'no_rekening'     => 'nullable|string|max:50',
            'tanggal_mulai'   => 'required|date',
            'tenor_bulan'     => 'required|integer|min:1|max:60',
            'total_gram'      => 'required|numeric|min:0',
            'angsuran_bulan'  => 'required|integer|min:0',
            'sewa_modal'      => 'nullable|integer|min:0',
            'biaya_admin'     => 'nullable|integer|min:0',
            'status'          => 'required|in:aktif,lunas,wanprestasi',
            'catatan'         => 'nullable|string|max:255',
            'file_kontrak'    => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $kontrak->file_kontrak;
        if ($request->hasFile('file_kontrak')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $request->file('file_kontrak')->store('kontrak', 'public');
        }

        $kontrak->update([
            'nomor_kontrak'   => $request->nomor_kontrak,
            'cabang'          => $request->cabang,
            'no_rekening'     => $request->no_rekening,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => now()->parse($request->tanggal_mulai)->addMonths((int) $request->tenor_bulan),
            'tenor_bulan'     => $request->tenor_bulan,
            'total_gram'      => $request->total_gram,
            'angsuran_bulan'  => $request->angsuran_bulan,
            'sewa_modal'      => $request->sewa_modal,
            'biaya_admin'     => $request->biaya_admin,
            'status'          => $request->status,
            'catatan'         => $request->catatan,
            'file_kontrak'    => $path,
        ]);

        return redirect()->route('kontrak-cicilan.index')
            ->with('success', 'Kontrak berhasil diupdate!');
    }

    public function destroy(KontrakCicilanEmas $kontrak)
    {
        abort_if($kontrak->user_id !== auth()->id(), 403);

        if ($kontrak->file_kontrak) {
            Storage::disk('public')->delete($kontrak->file_kontrak);
        }

        $kontrak->delete();

        return back()->with('success', 'Kontrak berhasil dihapus!');
    }
}
