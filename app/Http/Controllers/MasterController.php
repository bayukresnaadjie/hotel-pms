<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Layanan;
use App\Models\TarifMusiman;
use App\Models\TipeKamar;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    // ── Tipe kamar ────────────────────────────────────────────────────────────
    public function tipe()
    {
        $tipe = TipeKamar::withCount('kamar')->with('fasilitas')->orderBy('harga_dasar')->get();
        return view('master.tipe', compact('tipe'));
    }

    public function updateTipe(Request $request, TipeKamar $tipe)
    {
        $v = $request->validate([
            'nama'        => 'required|string|max:100',
            'harga_dasar' => 'required|numeric|min:50000',
            'deskripsi'   => 'nullable|string|max:500',
            'aktif'       => 'nullable|boolean',
        ]);

        $tipe->update($v + ['aktif' => $request->boolean('aktif')]);
        return back()->with('success', "Tipe {$tipe->nama} diperbarui.");
    }

    // ── Kamar ─────────────────────────────────────────────────────────────────
    public function kamar()
    {
        $kamar    = Kamar::with('tipeKamar')->orderBy('nomor')->get();
        $tipeList = TipeKamar::orderBy('harga_dasar')->get();
        return view('master.kamar', compact('kamar', 'tipeList'));
    }

    public function simpanKamar(Request $request)
    {
        $v = $request->validate([
            'tipe_kamar_id' => 'required|exists:tipe_kamar,id',
            'nomor'         => 'required|string|max:10|unique:kamar,nomor',
            'lantai'        => 'required|integer|min:1|max:50',
            'catatan'       => 'nullable|string|max:150',
        ]);

        Kamar::create($v);
        return back()->with('success', "Kamar {$v['nomor']} ditambahkan.");
    }

    public function updateKamar(Request $request, Kamar $kamar)
    {
        $v = $request->validate([
            'tipe_kamar_id' => 'required|exists:tipe_kamar,id',
            'lantai'        => 'required|integer|min:1|max:50',
            'catatan'       => 'nullable|string|max:150',
            'aktif'         => 'nullable|boolean',
        ]);

        $aktif = $request->boolean('aktif');
        if (!$aktif && $kamar->isTerisi()) {
            return back()->with('error', "Kamar {$kamar->nomor} sedang dihuni, tidak dapat dinonaktifkan.");
        }

        $kamar->update($v + ['aktif' => $aktif]);
        return back()->with('success', "Kamar {$kamar->nomor} diperbarui.");
    }

    // ── Layanan ───────────────────────────────────────────────────────────────
    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama')->get();
        return view('master.layanan', compact('layanan'));
    }

    public function simpanLayanan(Request $request)
    {
        $v = $request->validate([
            'kode'     => 'required|string|max:15|unique:layanan,kode',
            'nama'     => 'required|string|max:100',
            'kategori' => 'required|in:fnb,laundry,spa,transport,lainnya',
            'harga'    => 'required|numeric|min:1000',
        ]);

        Layanan::create($v);
        return back()->with('success', 'Layanan ditambahkan.');
    }

    public function updateLayanan(Request $request, Layanan $layanan)
    {
        $v = $request->validate([
            'nama'     => 'required|string|max:100',
            'kategori' => 'required|in:fnb,laundry,spa,transport,lainnya',
            'harga'    => 'required|numeric|min:1000',
        ]);

        $layanan->update($v + ['aktif' => $request->boolean('aktif')]);
        return back()->with('success', "Layanan {$layanan->nama} diperbarui.");
    }

    // ── Tarif musiman ─────────────────────────────────────────────────────────
    public function tarif()
    {
        $tarif    = TarifMusiman::with('tipeKamar')->orderByDesc('tanggal_mulai')->get();
        $tipeList = TipeKamar::orderBy('harga_dasar')->get();
        return view('master.tarif', compact('tarif', 'tipeList'));
    }

    public function simpanTarif(Request $request)
    {
        $v = $request->validate([
            'nama'            => 'required|string|max:100',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'pengali'         => 'required|numeric|min:0.5|max:5',
            'tipe_kamar_id'   => 'nullable|exists:tipe_kamar,id',
        ]);

        TarifMusiman::create($v);
        return back()->with('success', 'Tarif musiman ditambahkan. Berlaku untuk reservasi baru.');
    }

    public function hapusTarif(TarifMusiman $tarif)
    {
        $tarif->delete();
        return back()->with('success', 'Tarif musiman dihapus.');
    }
}
