<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Layanan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class FolioController extends Controller
{
    public function show(Folio $folio)
    {
        $folio->load(['reservasi.tamu', 'reservasi.kamar', 'reservasi.tipeKamar', 'items.layanan', 'pembayaran.diterimaOleh']);

        $layanan = Layanan::where('aktif', true)->orderBy('kategori')->orderBy('nama')->get();
        $itemPerTanggal = $folio->items->groupBy(fn($i) => $i->tanggal->toDateString());

        return view('folio.show', compact('folio', 'layanan', 'itemPerTanggal'));
    }

    public function invoice(Folio $folio)
    {
        $folio->load(['reservasi.tamu', 'reservasi.kamar', 'reservasi.tipeKamar', 'items', 'pembayaran']);
        return view('folio.invoice', compact('folio'));
    }

    public function tambahItem(Request $request, Folio $folio)
    {
        if ($folio->status !== 'terbuka' || $folio->reservasi->status !== 'check_in') {
            return back()->with('error', 'Biaya hanya dapat ditambahkan pada folio tamu yang sedang menginap.');
        }

        $v = $request->validate([
            'tipe'       => 'required|in:layanan,lainnya,diskon',
            'layanan_id' => 'required_if:tipe,layanan|nullable|exists:layanan,id',
            'deskripsi'  => 'required_if:tipe,lainnya,diskon|nullable|string|max:150',
            'harga'      => 'required_if:tipe,lainnya,diskon|nullable|numeric|min:1',
            'qty'        => 'nullable|integer|min:1|max:99',
            'tanggal'    => 'nullable|date|before_or_equal:today',
        ]);

        if ($v['tipe'] === 'diskon' && !$request->user()->punyaRole('admin', 'finance')) {
            return back()->with('error', 'Hanya admin atau finance yang dapat memberikan diskon.');
        }

        $qty     = (int) ($v['qty'] ?? 1);
        $tanggal = $v['tanggal'] ?? today()->toDateString();

        if ($v['tipe'] === 'layanan') {
            $ly = Layanan::findOrFail($v['layanan_id']);
            $deskripsi = $ly->nama;
            $harga = (float) $ly->harga;
            $jumlah = $harga * $qty;
        } elseif ($v['tipe'] === 'lainnya') {
            $deskripsi = $v['deskripsi'];
            $harga = (float) $v['harga'];
            $jumlah = $harga * $qty;
        } else {
            $deskripsi = $v['deskripsi'];
            $harga = -1 * (float) $v['harga'];
            $qty = 1;
            $jumlah = $harga;
        }

        FolioItem::create([
            'folio_id' => $folio->id, 'tanggal' => $tanggal, 'tipe' => $v['tipe'],
            'layanan_id' => $v['tipe'] === 'layanan' ? $ly->id : null,
            'deskripsi' => $deskripsi, 'qty' => $qty, 'harga_satuan' => $harga, 'jumlah' => $jumlah,
            'dibuat_oleh' => auth()->id(),
        ]);

        return back()->with('success', 'Biaya ditambahkan ke folio.');
    }

    public function hapusItem(Folio $folio, FolioItem $item)
    {
        if ($item->folio_id !== $folio->id || $folio->status !== 'terbuka') {
            return back()->with('error', 'Item tidak dapat dihapus.');
        }
        if ($item->tipe === 'kamar' && !auth()->user()->punyaRole('admin')) {
            return back()->with('error', 'Biaya kamar hasil night audit hanya dapat dihapus admin.');
        }
        if ($item->tipe === 'diskon' && !auth()->user()->punyaRole('admin', 'finance')) {
            return back()->with('error', 'Hanya admin atau finance yang dapat menghapus diskon.');
        }

        $item->delete();
        return back()->with('success', 'Item dihapus dari folio.');
    }

    public function bayar(Request $request, Folio $folio)
    {
        if ($folio->status !== 'terbuka') {
            return back()->with('error', 'Folio sudah ditutup.');
        }

        $v = $request->validate([
            'jenis'     => 'required|in:deposit,pembayaran,refund',
            'metode'    => 'required|in:tunai,kartu_debit,kartu_kredit,transfer,qris',
            'jumlah'    => 'required|numeric|min:1',
            'referensi' => 'nullable|string|max:100',
        ]);

        if ($v['jenis'] === 'refund') {
            if (!$request->user()->punyaRole('admin', 'finance')) {
                return back()->with('error', 'Hanya admin atau finance yang dapat melakukan refund.');
            }
            if ((float) $v['jumlah'] > (float) $folio->terbayar) {
                return back()->with('error', 'Jumlah refund melebihi total yang sudah dibayar.');
            }
        }

        Pembayaran::create($v + ['folio_id' => $folio->id, 'diterima_oleh' => auth()->id()]);

        return back()->with('success', 'Pembayaran dicatat.');
    }
}
