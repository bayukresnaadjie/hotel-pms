<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use Illuminate\Http\Request;

class KamarController extends Controller
{
    /** Rack kamar per lantai dengan status kebersihan dan penghuni saat ini. */
    public function index()
    {
        $kamar = Kamar::with(['tipeKamar', 'reservasiInHouse.tamu'])->where('aktif', true)->orderBy('nomor')->get();
        $perLantai = $kamar->groupBy('lantai');

        $ringkasan = [
            'terisi' => $kamar->filter(fn($k) => $k->reservasiInHouse !== null)->count(),
            'siap'   => $kamar->filter(fn($k) => $k->status_kamar === 'siap' && $k->reservasiInHouse === null)->count(),
            'kotor'  => $kamar->where('status_kamar', 'kotor')->count(),
            'dibersihkan' => $kamar->where('status_kamar', 'dibersihkan')->count(),
            'rusak'  => $kamar->where('status_kamar', 'rusak')->count(),
        ];

        return view('kamar.index', compact('perLantai', 'ringkasan'));
    }

    public function ubahStatus(Request $request, Kamar $kamar)
    {
        $v = $request->validate([
            'status_kamar' => 'required|in:siap,kotor,dibersihkan,rusak',
            'catatan'      => 'nullable|string|max:150',
        ]);

        if ($v['status_kamar'] === 'rusak') {
            if (!$request->user()->punyaRole('admin', 'frontdesk')) {
                return back()->with('error', 'Hanya admin atau front desk yang dapat menandai kamar rusak.');
            }
            if ($kamar->isTerisi()) {
                return back()->with('error', "Kamar {$kamar->nomor} sedang dihuni tamu. Pindahkan tamu terlebih dahulu.");
            }
        }

        $kamar->setStatus($v['status_kamar'], $v['catatan'] ?? null, auth()->id());

        return back()->with('success', "Status kamar {$kamar->nomor} diubah menjadi " . $kamar->fresh()->status_badge['label'] . '.');
    }
}
