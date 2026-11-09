<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Reservasi;
use App\Models\Tamu;
use App\Models\TipeKamar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservasiController extends Controller
{
    public function index(Request $request)
    {
        $reservasi = Reservasi::with(['tamu', 'tipeKamar', 'kamar'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = $request->q;
                $q->where(function ($w) use ($kata) {
                    $w->where('kode_booking', 'like', "%{$kata}%")
                      ->orWhereHas('tamu', fn($t) => $t->where('nama', 'like', "%{$kata}%")->orWhere('telepon', 'like', "%{$kata}%"));
                });
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('sumber'), fn($q) => $q->where('sumber', $request->sumber))
            ->when($request->filled('dari'), fn($q) => $q->whereDate('tanggal_masuk', '>=', $request->dari))
            ->when($request->filled('sampai'), fn($q) => $q->whereDate('tanggal_masuk', '<=', $request->sampai))
            ->orderByDesc('tanggal_masuk')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('reservasi.index', compact('reservasi'));
    }

    public function create(Request $request)
    {
        $tipeList = TipeKamar::where('aktif', true)->orderBy('harga_dasar')->get();
        $tamuList = Tamu::where('tipe', '!=', 'blacklist')->orderBy('nama')->get(['id', 'nama', 'telepon', 'tipe']);

        return view('reservasi.create', [
            'tipeList' => $tipeList,
            'tamuList' => $tamuList,
            'tamuId'   => $request->get('tamu_id'),
            'masuk'    => $request->get('masuk', today()->toDateString()),
            'keluar'   => $request->get('keluar', today()->addDay()->toDateString()),
        ]);
    }

    /** JSON: ketersediaan dan total tarif tiap tipe kamar untuk rentang tanggal (dipakai form reservasi). */
    public function ketersediaan(Request $request)
    {
        $request->validate(['masuk' => 'required|date', 'keluar' => 'required|date|after:masuk']);

        $hasil = TipeKamar::where('aktif', true)->orderBy('harga_dasar')->get()->map(function ($t) use ($request) {
            $rincian = $t->rincianTarif($request->masuk, $request->keluar);
            return [
                'id'        => $t->id,
                'nama'      => $t->nama,
                'tersedia'  => $t->tersedia($request->masuk, $request->keluar),
                'total'     => $t->totalTarif($request->masuk, $request->keluar),
                'malam'     => count($rincian),
                'rata_rata' => count($rincian) ? array_sum(array_column($rincian, 'harga')) / count($rincian) : 0,
            ];
        });

        return response()->json($hasil);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'tamu_id'           => 'nullable|exists:tamu,id',
            'nama_tamu'         => 'required_without:tamu_id|nullable|string|max:100',
            'telepon_tamu'      => 'required_without:tamu_id|nullable|string|max:20',
            'email_tamu'        => 'nullable|email|max:100',
            'tipe_kamar_id'     => 'required|exists:tipe_kamar,id',
            'tanggal_masuk'     => 'required|date|after_or_equal:today',
            'tanggal_keluar'    => 'required|date|after:tanggal_masuk',
            'jumlah_dewasa'     => 'required|integer|min:1|max:10',
            'jumlah_anak'       => 'required|integer|min:0|max:10',
            'sumber'            => 'required|in:walk_in,telepon,website,ota',
            'deposit'           => 'nullable|numeric|min:0',
            'metode_deposit'    => 'nullable|in:tunai,kartu_debit,kartu_kredit,transfer,qris',
            'permintaan_khusus' => 'nullable|string|max:500',
        ]);

        $reservasi = DB::transaction(function () use ($v) {
            // Kunci baris tipe kamar agar dua booking bersamaan tidak sama-sama lolos cek stok
            $tipe = TipeKamar::lockForUpdate()->findOrFail($v['tipe_kamar_id']);
            $this->validasiKamar($tipe, $v, null);

            if (!empty($v['tamu_id'])) {
                $tamu = Tamu::findOrFail($v['tamu_id']);
                if ($tamu->tipe === 'blacklist') {
                    throw ValidationException::withMessages(['tamu_id' => 'Tamu ini masuk daftar blacklist dan tidak dapat melakukan reservasi.']);
                }
            } else {
                $tamu = Tamu::create(['nama' => $v['nama_tamu'], 'telepon' => $v['telepon_tamu'], 'email' => $v['email_tamu'] ?? null]);
            }

            $deposit = (float) ($v['deposit'] ?? 0);
            $r = Reservasi::create([
                'tamu_id'           => $tamu->id,
                'tipe_kamar_id'     => $tipe->id,
                'tanggal_masuk'     => $v['tanggal_masuk'],
                'tanggal_keluar'    => $v['tanggal_keluar'],
                'jumlah_dewasa'     => $v['jumlah_dewasa'],
                'jumlah_anak'       => $v['jumlah_anak'],
                'sumber'            => $v['sumber'],
                'status'            => 'dikonfirmasi',
                'total_kamar'       => $tipe->totalTarif($v['tanggal_masuk'], $v['tanggal_keluar']),
                'deposit'           => $deposit,
                'permintaan_khusus' => $v['permintaan_khusus'] ?? null,
                'dibuat_oleh'       => auth()->id(),
            ]);

            $folio = Folio::untuk($r);
            if ($deposit > 0) {
                Pembayaran::create([
                    'folio_id' => $folio->id, 'jenis' => 'deposit', 'metode' => $v['metode_deposit'] ?? 'transfer',
                    'jumlah' => $deposit, 'diterima_oleh' => auth()->id(),
                ]);
            }

            return $r;
        });

        return redirect()->route('reservasi.show', $reservasi)->with('success', 'Reservasi ' . $reservasi->kode_booking . ' berhasil dibuat.');
    }

    public function show(Reservasi $reservasi)
    {
        $reservasi->load(['tamu', 'tipeKamar', 'kamar', 'dibuatOleh', 'folio.pembayaran']);

        $rincian = $reservasi->tipeKamar->rincianTarif($reservasi->tanggal_masuk, $reservasi->tanggal_keluar);

        // Kamar yang bisa dipilih untuk pindah kamar / check-in
        $kamarPilihan = collect();
        if ($reservasi->status === 'check_in') {
            $kamarPilihan = Kamar::siapUntuk($reservasi, today());
        } elseif ($reservasi->bisaCheckIn()) {
            $kamarPilihan = Kamar::siapUntuk($reservasi, today());
        }

        return view('reservasi.show', compact('reservasi', 'rincian', 'kamarPilihan'));
    }

    public function edit(Reservasi $reservasi)
    {
        if ($reservasi->status !== 'dikonfirmasi') {
            return redirect()->route('reservasi.show', $reservasi)->with('error', 'Hanya reservasi berstatus Dikonfirmasi yang dapat diubah.');
        }

        $tipeList = TipeKamar::where('aktif', true)->orderBy('harga_dasar')->get();
        return view('reservasi.edit', compact('reservasi', 'tipeList'));
    }

    public function update(Request $request, Reservasi $reservasi)
    {
        if ($reservasi->status !== 'dikonfirmasi') {
            return back()->with('error', 'Hanya reservasi berstatus Dikonfirmasi yang dapat diubah.');
        }

        $v = $request->validate([
            'tipe_kamar_id'     => 'required|exists:tipe_kamar,id',
            'tanggal_masuk'     => 'required|date|after_or_equal:today',
            'tanggal_keluar'    => 'required|date|after:tanggal_masuk',
            'jumlah_dewasa'     => 'required|integer|min:1|max:10',
            'jumlah_anak'       => 'required|integer|min:0|max:10',
            'permintaan_khusus' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($v, $reservasi) {
            $tipe = TipeKamar::lockForUpdate()->findOrFail($v['tipe_kamar_id']);
            $this->validasiKamar($tipe, $v, $reservasi->id);

            $reservasi->update([
                'tipe_kamar_id'     => $tipe->id,
                'tanggal_masuk'     => $v['tanggal_masuk'],
                'tanggal_keluar'    => $v['tanggal_keluar'],
                'jumlah_dewasa'     => $v['jumlah_dewasa'],
                'jumlah_anak'       => $v['jumlah_anak'],
                'permintaan_khusus' => $v['permintaan_khusus'] ?? null,
                'total_kamar'       => $tipe->totalTarif($v['tanggal_masuk'], $v['tanggal_keluar']),
            ]);
        });

        return redirect()->route('reservasi.show', $reservasi)->with('success', 'Reservasi berhasil diperbarui.');
    }

    public function batal(Request $request, Reservasi $reservasi)
    {
        if (!$reservasi->bisaDibatalkan()) {
            return back()->with('error', 'Reservasi ini tidak dapat dibatalkan.');
        }

        $v = $request->validate(['alasan_batal' => 'required|string|max:200', 'refund_deposit' => 'nullable|boolean']);

        DB::transaction(function () use ($reservasi, $v, $request) {
            $reservasi->update(['status' => 'dibatalkan', 'dibatalkan_at' => now(), 'alasan_batal' => $v['alasan_batal']]);

            $folio = $reservasi->folio;
            if ($folio) {
                $terbayar = (float) $folio->terbayar;
                if ($request->boolean('refund_deposit') && $terbayar > 0) {
                    Pembayaran::create([
                        'folio_id' => $folio->id, 'jenis' => 'refund', 'metode' => 'transfer',
                        'jumlah' => $terbayar, 'referensi' => 'Refund pembatalan ' . $reservasi->kode_booking, 'diterima_oleh' => auth()->id(),
                    ]);
                }
                $folio->refresh();
                $folio->update(['status' => 'tertutup', 'ditutup_at' => now()]);
            }
        });

        return redirect()->route('reservasi.show', $reservasi)->with('success', 'Reservasi dibatalkan.');
    }

    public function noShow(Reservasi $reservasi)
    {
        if ($reservasi->status !== 'dikonfirmasi' || today()->lt($reservasi->tanggal_masuk)) {
            return back()->with('error', 'Tamu hanya dapat ditandai no-show pada atau setelah tanggal kedatangan.');
        }

        DB::transaction(function () use ($reservasi) {
            $reservasi->update(['status' => 'no_show']);
            $reservasi->folio?->update(['status' => 'tertutup', 'ditutup_at' => now()]);
        });

        return redirect()->route('reservasi.show', $reservasi)->with('success', 'Reservasi ditandai no-show. Stok kamar dilepas.');
    }

    /** Validasi kapasitas, status tipe, dan stok untuk rentang malam. Dipanggil di dalam transaksi. */
    private function validasiKamar(TipeKamar $tipe, array $v, ?int $abaikanReservasiId): void
    {
        if (!$tipe->aktif) {
            throw ValidationException::withMessages(['tipe_kamar_id' => 'Tipe kamar ini tidak aktif.']);
        }
        if ((int) $v['jumlah_dewasa'] > $tipe->kapasitas_dewasa || (int) $v['jumlah_anak'] > $tipe->kapasitas_anak) {
            throw ValidationException::withMessages(['jumlah_dewasa' => "Melebihi kapasitas {$tipe->nama} ({$tipe->kapasitas_dewasa} dewasa, {$tipe->kapasitas_anak} anak)."]);
        }
        if ($tipe->tersedia($v['tanggal_masuk'], $v['tanggal_keluar'], $abaikanReservasiId) < 1) {
            throw ValidationException::withMessages(['tipe_kamar_id' => "{$tipe->nama} tidak tersedia pada " . Carbon::parse($v['tanggal_masuk'])->format('d M') . ' – ' . Carbon::parse($v['tanggal_keluar'])->format('d M Y') . '.']);
        }
    }
}
