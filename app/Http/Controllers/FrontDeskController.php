<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\HousekeepingTask;
use App\Models\Kamar;
use App\Models\Pembayaran;
use App\Models\Reservasi;
use App\Models\TipeKamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FrontDeskController extends Controller
{
    public function index()
    {
        $hariIni = today();

        // Kedatangan hari ini + tamu terlambat yang belum datang tapi masih dalam masa menginap
        $kedatangan = Reservasi::where('status', 'dikonfirmasi')
            ->whereDate('tanggal_masuk', '<=', $hariIni)->whereDate('tanggal_keluar', '>', $hariIni)
            ->with(['tamu', 'tipeKamar', 'folio'])->orderBy('tanggal_masuk')->orderBy('id')->get();

        // Keberangkatan hari ini + tamu yang melewati tanggal keluar (overstay)
        $keberangkatan = Reservasi::where('status', 'check_in')->whereDate('tanggal_keluar', '<=', $hariIni)
            ->with(['tamu', 'kamar', 'folio'])->orderBy('tanggal_keluar')->get();

        $inHouse = Reservasi::where('status', 'check_in')->whereDate('tanggal_keluar', '>', $hariIni)
            ->with(['tamu', 'kamar', 'tipeKamar', 'folio'])->orderBy('kamar_id')->get();

        $kamarSiap = Kamar::where('status_kamar', 'siap')->where('aktif', true)->whereDoesntHave('reservasiInHouse')
            ->selectRaw('tipe_kamar_id, COUNT(*) as total')->groupBy('tipe_kamar_id')->pluck('total', 'tipe_kamar_id');
        $tipeList = TipeKamar::where('aktif', true)->orderBy('harga_dasar')->get();

        return view('frontdesk.index', compact('kedatangan', 'keberangkatan', 'inHouse', 'kamarSiap', 'tipeList', 'hariIni'));
    }

    // ── Check-in ──────────────────────────────────────────────────────────────
    public function checkInForm(Reservasi $reservasi)
    {
        if (!$reservasi->bisaCheckIn()) {
            return redirect()->route('reservasi.show', $reservasi)->with('error', 'Reservasi ini belum/tidak dapat di-check-in.');
        }

        $reservasi->load(['tamu', 'tipeKamar', 'folio']);
        $kamarSiap  = Kamar::siapUntuk($reservasi, today());
        $kamarBelum = Kamar::where('tipe_kamar_id', $reservasi->tipe_kamar_id)->where('aktif', true)
            ->whereIn('status_kamar', ['kotor', 'dibersihkan'])->count();

        return view('frontdesk.checkin', compact('reservasi', 'kamarSiap', 'kamarBelum'));
    }

    public function checkIn(Request $request, Reservasi $reservasi)
    {
        $v = $request->validate([
            'kamar_id'        => 'required|exists:kamar,id',
            'jenis_identitas' => 'nullable|in:ktp,paspor,sim',
            'nomor_identitas' => 'nullable|string|max:30',
        ]);

        $folio = DB::transaction(function () use ($reservasi, $v) {
            $r     = Reservasi::lockForUpdate()->findOrFail($reservasi->id);
            $kamar = Kamar::lockForUpdate()->findOrFail($v['kamar_id']);
            $tamu  = $r->tamu;

            if (!$r->bisaCheckIn()) {
                throw ValidationException::withMessages(['kamar_id' => 'Reservasi ini tidak dapat di-check-in.']);
            }
            if ($tamu->tipe === 'blacklist') {
                throw ValidationException::withMessages(['kamar_id' => 'Tamu ini masuk daftar blacklist. Hubungi manajer.']);
            }
            if ($kamar->tipe_kamar_id !== $r->tipe_kamar_id || !$kamar->aktif || $kamar->status_kamar !== 'siap'
                || $kamar->isTerisi() || !$kamar->tersediaUntuk(today(), $r->tanggal_keluar, $r->id)) {
                throw ValidationException::withMessages(['kamar_id' => "Kamar {$kamar->nomor} tidak dapat dipakai (tidak siap, terisi, atau bentrok)."]);
            }
            if (empty($tamu->nomor_identitas) && empty($v['nomor_identitas'])) {
                throw ValidationException::withMessages(['nomor_identitas' => 'Nomor identitas tamu wajib diisi saat check-in.']);
            }
            if (!empty($v['nomor_identitas'])) {
                $tamu->update(['nomor_identitas' => $v['nomor_identitas'], 'jenis_identitas' => $v['jenis_identitas'] ?? $tamu->jenis_identitas]);
            }

            $r->update(['status' => 'check_in', 'kamar_id' => $kamar->id, 'check_in_at' => now()]);

            return Folio::untuk($r);
        });

        return redirect()->route('folio.show', $folio)->with('success', 'Check-in berhasil. Tamu dapat menempati kamar.');
    }

    // ── Check-out ─────────────────────────────────────────────────────────────
    public function checkOutForm(Reservasi $reservasi)
    {
        if (!$reservasi->bisaCheckOut()) {
            return redirect()->route('reservasi.show', $reservasi)->with('error', 'Tamu ini tidak sedang menginap.');
        }

        $reservasi->load(['tamu', 'kamar', 'tipeKamar']);
        $folio = Folio::untuk($reservasi);
        $folio->load(['items.layanan', 'pembayaran']);
        $keluarAktual = $reservasi->tanggalKeluarAktual();
        $proyeksi     = $folio->proyeksiCheckout($reservasi, $keluarAktual);

        return view('frontdesk.checkout', compact('reservasi', 'folio', 'keluarAktual', 'proyeksi'));
    }

    public function checkOut(Request $request, Reservasi $reservasi)
    {
        $v = $request->validate([
            'metode'    => 'nullable|in:tunai,kartu_debit,kartu_kredit,transfer,qris',
            'referensi' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($reservasi, $v) {
            $r = Reservasi::lockForUpdate()->findOrFail($reservasi->id);
            if (!$r->bisaCheckOut()) {
                throw ValidationException::withMessages(['metode' => 'Tamu ini tidak sedang menginap.']);
            }

            $keluar = $r->tanggalKeluarAktual();
            $folio  = Folio::untuk($r);

            // Posting semua malam yang belum tercatat (termasuk kelebihan inap)
            $tgl = $r->tanggal_masuk->copy()->startOfDay();
            while ($tgl->lt($keluar)) {
                $folio->postingKamar($r, $tgl, auth()->id());
                $tgl->addDay();
            }
            $folio->refresh();

            $sisa = (float) $folio->total - (float) $folio->terbayar;
            if ($sisa > 0.5) {
                if (empty($v['metode'])) {
                    throw ValidationException::withMessages(['metode' => 'Pilih metode pembayaran untuk melunasi sisa tagihan.']);
                }
                Pembayaran::create([
                    'folio_id' => $folio->id, 'jenis' => 'pembayaran', 'metode' => $v['metode'],
                    'jumlah' => $sisa, 'referensi' => $v['referensi'] ?? null, 'diterima_oleh' => auth()->id(),
                ]);
            } elseif ($sisa < -0.5) {
                // Deposit melebihi tagihan: kembalikan selisihnya
                Pembayaran::create([
                    'folio_id' => $folio->id, 'jenis' => 'refund', 'metode' => $v['metode'] ?? 'tunai',
                    'jumlah' => abs($sisa), 'referensi' => 'Pengembalian kelebihan deposit', 'diterima_oleh' => auth()->id(),
                ]);
            }

            $folio->refresh();
            $folio->update(['status' => 'tertutup', 'ditutup_at' => now()]);

            $r->update([
                'status'         => 'check_out',
                'check_out_at'   => now(),
                'tanggal_keluar' => $keluar->toDateString(),
                'total_kamar'    => (float) $folio->items()->where('tipe', 'kamar')->sum('jumlah'),
            ]);

            // Kamar jadi kotor dan masuk antrean housekeeping
            if ($r->kamar) {
                $r->kamar->setStatus('kotor', 'Check-out ' . $r->kode_booking, auth()->id());

                $tugas = HousekeepingTask::where('kamar_id', $r->kamar_id)->whereDate('tanggal', today())
                    ->where('tipe', 'checkout_clean')->where('status', 'menunggu')->first();
                if ($tugas) {
                    $tugas->update(['catatan' => 'Check-out ' . $r->kode_booking]);
                } else {
                    HousekeepingTask::create([
                        'kamar_id' => $r->kamar_id, 'tipe' => 'checkout_clean', 'status' => 'menunggu',
                        'tanggal' => today()->toDateString(), 'catatan' => 'Check-out ' . $r->kode_booking,
                    ]);
                }
            }
        });

        return redirect()->route('folio.show', $reservasi->folio)->with('success', 'Check-out berhasil. Folio ditutup.');
    }

    // ── Pindah kamar & perpanjang ─────────────────────────────────────────────
    public function gantiKamar(Request $request, Reservasi $reservasi)
    {
        $v = $request->validate(['kamar_id' => 'required|exists:kamar,id', 'alasan' => 'nullable|string|max:150']);

        DB::transaction(function () use ($reservasi, $v) {
            $r        = Reservasi::lockForUpdate()->findOrFail($reservasi->id);
            $baru     = Kamar::lockForUpdate()->findOrFail($v['kamar_id']);
            $lama     = $r->kamar;

            if ($r->status !== 'check_in') {
                throw ValidationException::withMessages(['kamar_id' => 'Pindah kamar hanya untuk tamu yang sedang menginap.']);
            }
            if ($baru->tipe_kamar_id !== $r->tipe_kamar_id || $baru->status_kamar !== 'siap' || $baru->isTerisi()
                || !$baru->tersediaUntuk(today(), $r->tanggal_keluar, $r->id)) {
                throw ValidationException::withMessages(['kamar_id' => "Kamar {$baru->nomor} tidak dapat dipakai untuk pindah kamar."]);
            }

            $r->update(['kamar_id' => $baru->id]);

            if ($lama) {
                $lama->setStatus('kotor', 'Tamu pindah ke kamar ' . $baru->nomor . ($v['alasan'] ? ' - ' . $v['alasan'] : ''), auth()->id());
                HousekeepingTask::create([
                    'kamar_id' => $lama->id, 'tipe' => 'checkout_clean', 'status' => 'menunggu',
                    'tanggal' => today()->toDateString(), 'catatan' => 'Tamu pindah kamar (' . $r->kode_booking . ')',
                ]);
            }
        });

        return back()->with('success', 'Tamu berhasil dipindahkan ke kamar baru.');
    }

    public function perpanjang(Request $request, Reservasi $reservasi)
    {
        if (!in_array($reservasi->status, ['dikonfirmasi', 'check_in'], true)) {
            return back()->with('error', 'Reservasi ini tidak dapat diperpanjang.');
        }

        $v = $request->validate(['tanggal_keluar' => 'required|date|after:' . $reservasi->tanggal_keluar->toDateString()]);

        DB::transaction(function () use ($reservasi, $v) {
            $tipe = TipeKamar::lockForUpdate()->findOrFail($reservasi->tipe_kamar_id);
            $r    = Reservasi::lockForUpdate()->findOrFail($reservasi->id);
            $lama = $r->tanggal_keluar->copy();

            // Malam tambahan = [keluar lama, keluar baru)
            if ($tipe->tersedia($lama, $v['tanggal_keluar'], $r->id) < 1) {
                throw ValidationException::withMessages(['tanggal_keluar' => 'Kamar tidak tersedia untuk malam tambahan tersebut.']);
            }
            if ($r->kamar && !$r->kamar->tersediaUntuk($lama, $v['tanggal_keluar'], $r->id)) {
                throw ValidationException::withMessages(['tanggal_keluar' => "Kamar {$r->kamar->nomor} sudah dipesan tamu lain pada malam tambahan. Pindahkan tamu terlebih dahulu."]);
            }

            $r->update([
                'tanggal_keluar' => $v['tanggal_keluar'],
                'total_kamar'    => $tipe->totalTarif($r->tanggal_masuk, $v['tanggal_keluar']),
            ]);
        });

        return back()->with('success', 'Masa menginap berhasil diperpanjang.');
    }
}
