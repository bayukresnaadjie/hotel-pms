<?php

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Models\Kamar;
use App\Models\LaporanKerusakan;
use App\Models\Reservasi;
use App\Models\User;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    public function index()
    {
        $hariIni = today();
        $urutan  = ['dikerjakan' => 0, 'menunggu' => 1, 'selesai' => 2];

        $tugas = HousekeepingTask::with(['kamar.tipeKamar', 'petugas'])->whereDate('tanggal', $hariIni)->get()
            ->sortBy(fn($t) => $urutan[$t->status] . str_pad($t->kamar->nomor, 5, '0', STR_PAD_LEFT))->values();

        // Kamar kotor yang belum punya tugas aktif hari ini
        $kamarTanpaTugas = Kamar::where('status_kamar', 'kotor')->where('aktif', true)
            ->whereDoesntHave('tasks', fn($q) => $q->whereDate('tanggal', $hariIni)->where('status', '!=', 'selesai'))
            ->orderBy('nomor')->get();

        $petugas = User::where('role', 'housekeeping')->where('aktif', true)->orderBy('name')->get();

        $stat = [
            'menunggu'   => $tugas->where('status', 'menunggu')->count(),
            'dikerjakan' => $tugas->where('status', 'dikerjakan')->count(),
            'selesai'    => $tugas->where('status', 'selesai')->count(),
            'kotor'      => Kamar::where('status_kamar', 'kotor')->count(),
            'rusak'      => Kamar::where('status_kamar', 'rusak')->count(),
        ];

        return view('housekeeping.index', compact('tugas', 'kamarTanpaTugas', 'petugas', 'stat'));
    }

    public function buatTugas(Request $request)
    {
        $v = $request->validate(['kamar_id' => 'required|exists:kamar,id', 'tipe' => 'nullable|in:checkout_clean,stayover,inspeksi']);

        HousekeepingTask::create([
            'kamar_id' => $v['kamar_id'], 'tipe' => $v['tipe'] ?? 'checkout_clean',
            'status' => 'menunggu', 'tanggal' => today()->toDateString(),
        ]);

        return back()->with('success', 'Tugas pembersihan dibuat.');
    }

    public function tugaskan(Request $request, HousekeepingTask $tugas)
    {
        $v = $request->validate(['ditugaskan_ke' => 'required|exists:users,id']);
        $tugas->update(['ditugaskan_ke' => $v['ditugaskan_ke']]);
        return back()->with('success', 'Tugas ditugaskan.');
    }

    public function mulai(HousekeepingTask $tugas)
    {
        if ($tugas->status !== 'menunggu') {
            return back()->with('error', 'Tugas ini sudah dimulai atau selesai.');
        }

        $data = ['status' => 'dikerjakan', 'mulai_at' => now()];
        if (!$tugas->ditugaskan_ke && auth()->user()->punyaRole('housekeeping')) {
            $data['ditugaskan_ke'] = auth()->id();
        }
        $tugas->update($data);

        if ($tugas->tipe === 'checkout_clean' && $tugas->kamar->status_kamar === 'kotor') {
            $tugas->kamar->setStatus('dibersihkan', 'Mulai dibersihkan', auth()->id());
        }

        return back()->with('success', 'Tugas dimulai.');
    }

    public function selesai(HousekeepingTask $tugas)
    {
        if ($tugas->status === 'selesai') {
            return back()->with('error', 'Tugas ini sudah selesai.');
        }

        $tugas->update(['status' => 'selesai', 'selesai_at' => now(), 'mulai_at' => $tugas->mulai_at ?? now()]);

        $kamar = $tugas->kamar;
        if (in_array($tugas->tipe, ['checkout_clean', 'inspeksi'], true) && in_array($kamar->status_kamar, ['kotor', 'dibersihkan'], true)) {
            $kamar->setStatus('siap', 'Selesai dibersihkan', auth()->id());
        }

        return back()->with('success', "Kamar {$kamar->nomor} selesai dibersihkan dan siap dijual.");
    }

    // ── Laporan kerusakan ─────────────────────────────────────────────────────
    public function kerusakan()
    {
        $urutanStatus = ['dilaporkan' => 0, 'diproses' => 1, 'selesai' => 2];
        $urutanPrioritas = ['tinggi' => 0, 'sedang' => 1, 'rendah' => 2];

        $laporan = LaporanKerusakan::with(['kamar.tipeKamar', 'pelapor'])->get()
            ->sortBy(fn($l) => $urutanStatus[$l->status] . $urutanPrioritas[$l->prioritas] . str_pad((string) (999999 - $l->id), 6, '0', STR_PAD_LEFT))
            ->values();

        $kamarList = Kamar::where('aktif', true)->orderBy('nomor')->get();

        return view('housekeeping.kerusakan', compact('laporan', 'kamarList'));
    }

    public function simpanKerusakan(Request $request)
    {
        $v = $request->validate([
            'kamar_id'  => 'required|exists:kamar,id',
            'judul'     => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:500',
            'prioritas' => 'required|in:rendah,sedang,tinggi',
            'kamar_tidak_bisa_dipakai' => 'nullable|boolean',
        ]);

        $tidakBisa = $request->boolean('kamar_tidak_bisa_dipakai');
        $kamar = Kamar::findOrFail($v['kamar_id']);

        $laporan = LaporanKerusakan::create([
            'kamar_id' => $kamar->id, 'dilaporkan_oleh' => auth()->id(), 'judul' => $v['judul'],
            'deskripsi' => $v['deskripsi'] ?? null, 'prioritas' => $v['prioritas'],
            'kamar_tidak_bisa_dipakai' => $tidakBisa,
        ]);

        $pesan = 'Laporan kerusakan dicatat.';

        if ($tidakBisa) {
            if ($kamar->isTerisi()) {
                $laporan->update(['kamar_tidak_bisa_dipakai' => false]);
                return back()->with('error', "Laporan dicatat, tetapi kamar {$kamar->nomor} sedang dihuni sehingga belum bisa ditandai rusak. Pindahkan tamu terlebih dahulu.");
            }

            $kamar->setStatus('rusak', $v['judul'], auth()->id());

            // Peringatan bila stok tipe kamar kini kurang dari reservasi mendatang
            $tanggalBermasalah = $this->cekKekuranganStok($kamar);
            if ($tanggalBermasalah) {
                return back()->with('error', "Kamar {$kamar->nomor} ditandai rusak. PERHATIAN: stok {$kamar->tipeKamar->nama} kini kurang dari reservasi pada {$tanggalBermasalah}. Atur ulang reservasi.");
            }
            $pesan .= " Kamar {$kamar->nomor} ditandai rusak (out of order).";
        }

        return back()->with('success', $pesan);
    }

    public function updateKerusakan(Request $request, LaporanKerusakan $laporan)
    {
        $v = $request->validate(['status' => 'required|in:diproses,selesai']);

        $laporan->update([
            'status'          => $v['status'],
            'diselesaikan_at' => $v['status'] === 'selesai' ? now() : null,
        ]);

        // Kamar kembali dijual bila tidak ada lagi laporan yang memblokir kamar tersebut
        if ($v['status'] === 'selesai') {
            $kamar = $laporan->kamar;
            $masihBlokir = LaporanKerusakan::where('kamar_id', $kamar->id)->where('kamar_tidak_bisa_dipakai', true)
                ->where('status', '!=', 'selesai')->where('id', '!=', $laporan->id)->exists();

            if ($kamar->status_kamar === 'rusak' && !$masihBlokir) {
                $kamar->setStatus('kotor', 'Perbaikan selesai: ' . $laporan->judul, auth()->id());
                HousekeepingTask::create([
                    'kamar_id' => $kamar->id, 'tipe' => 'checkout_clean', 'status' => 'menunggu',
                    'tanggal' => today()->toDateString(), 'catatan' => 'Bersihkan setelah perbaikan',
                ]);
                return back()->with('success', "Perbaikan selesai. Kamar {$kamar->nomor} masuk antrean pembersihan.");
            }
        }

        return back()->with('success', 'Status laporan diperbarui.');
    }

    /** Tanggal pertama (60 hari ke depan) saat reservasi aktif melebihi jumlah kamar jual; null bila aman. */
    private function cekKekuranganStok(Kamar $kamar): ?string
    {
        $tipe  = $kamar->tipeKamar;
        $jual  = $tipe->jumlahKamarJual();

        for ($i = 0; $i < 60; $i++) {
            $tgl = today()->copy()->addDays($i);
            if ($tipe->jumlahTerpakai($tgl, $tgl->copy()->addDay()) > $jual) {
                return $tgl->format('d M Y');
            }
        }
        return null;
    }
}
