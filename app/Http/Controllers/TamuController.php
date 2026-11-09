<?php

namespace App\Http\Controllers;

use App\Models\Tamu;
use Illuminate\Http\Request;

class TamuController extends Controller
{
    public function index(Request $request)
    {
        $tamu = Tamu::withCount(['reservasi as total_reservasi'])
            ->when($request->filled('q'), fn($q) => $q->where(function ($w) use ($request) {
                $w->where('nama', 'like', "%{$request->q}%")->orWhere('telepon', 'like', "%{$request->q}%")
                  ->orWhere('email', 'like', "%{$request->q}%")->orWhere('nomor_identitas', 'like', "%{$request->q}%");
            }))
            ->when($request->filled('tipe'), fn($q) => $q->where('tipe', $request->tipe))
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('tamu.index', compact('tamu'));
    }

    public function create()
    {
        return view('tamu.form', ['tamu' => new Tamu()]);
    }

    public function store(Request $request)
    {
        $tamu = Tamu::create($this->validasi($request));
        return redirect()->route('tamu.show', $tamu)->with('success', 'Tamu berhasil ditambahkan.');
    }

    public function show(Tamu $tamu)
    {
        $riwayat = $tamu->reservasi()->with(['tipeKamar', 'kamar', 'folio'])->orderByDesc('tanggal_masuk')->get();

        $ringkasan = [
            'menginap' => $riwayat->where('status', 'check_out')->count(),
            'malam'    => (int) $riwayat->where('status', 'check_out')->sum('jumlah_malam'),
            'belanja'  => (float) $riwayat->where('status', 'check_out')->sum(fn($r) => (float) ($r->folio->total ?? 0)),
        ];

        return view('tamu.show', compact('tamu', 'riwayat', 'ringkasan'));
    }

    public function edit(Tamu $tamu)
    {
        return view('tamu.form', compact('tamu'));
    }

    public function update(Request $request, Tamu $tamu)
    {
        $tamu->update($this->validasi($request));
        return redirect()->route('tamu.show', $tamu)->with('success', 'Data tamu diperbarui.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama'            => 'required|string|max:100',
            'email'           => 'nullable|email|max:100',
            'telepon'         => 'nullable|string|max:20',
            'jenis_identitas' => 'required|in:ktp,paspor,sim',
            'nomor_identitas' => 'nullable|string|max:30',
            'alamat'          => 'nullable|string',
            'kota'            => 'nullable|string|max:60',
            'negara'          => 'required|string|max:60',
            'tanggal_lahir'   => 'nullable|date|before:today',
            'tipe'            => 'required|in:reguler,vip,blacklist',
            'catatan'         => 'nullable|string|max:500',
        ]);
    }
}
