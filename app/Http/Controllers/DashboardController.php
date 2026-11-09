<?php

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Models\Kamar;
use App\Models\NightAudit;
use App\Models\Pembayaran;
use App\Models\Reservasi;
use App\Models\TipeKamar;

class DashboardController extends Controller
{
    public function index()
    {
        $hariIni = today();
        $jual    = Kamar::where('aktif', true)->where('status_kamar', '!=', 'rusak')->count();
        $inHouse = Reservasi::inHouse()->count();

        $kasMasuk  = (float) Pembayaran::whereDate('created_at', $hariIni)->whereIn('jenis', ['deposit', 'pembayaran'])->sum('jumlah');
        $kasKeluar = (float) Pembayaran::whereDate('created_at', $hariIni)->where('jenis', 'refund')->sum('jumlah');

        $stat = [
            'jual'          => $jual,
            'in_house'      => $inHouse,
            'okupansi'      => $jual > 0 ? round($inHouse / $jual * 100, 1) : 0,
            'kedatangan'    => Reservasi::kedatanganHariIni()->count(),
            'keberangkatan' => Reservasi::keberangkatanHariIni()->count(),
            'kamar_kotor'   => Kamar::where('status_kamar', 'kotor')->count(),
            'kamar_rusak'   => Kamar::where('status_kamar', 'rusak')->count(),
            'kas_masuk'     => $kasMasuk - $kasKeluar,
            'tugas_hk'      => HousekeepingTask::whereDate('tanggal', $hariIni)->where('status', '!=', 'selesai')->count(),
            // perlu tindakan front desk
            'terlambat_datang' => Reservasi::where('status', 'dikonfirmasi')->whereDate('tanggal_masuk', '<', $hariIni)->whereDate('tanggal_keluar', '>', $hariIni)->count(),
            'overstay'         => Reservasi::where('status', 'check_in')->whereDate('tanggal_keluar', '<', $hariIni)->count(),
        ];

        $kedatangan    = Reservasi::kedatanganHariIni()->with(['tamu', 'tipeKamar'])->orderBy('id')->get();
        $keberangkatan = Reservasi::keberangkatanHariIni()->with(['tamu', 'kamar', 'folio'])->orderBy('id')->get();

        $statusKamar = Kamar::where('aktif', true)->selectRaw('status_kamar, COUNT(*) as total')->groupBy('status_kamar')->pluck('total', 'status_kamar');

        $besok = $hariIni->copy()->addDay();
        $ketersediaan = TipeKamar::where('aktif', true)->orderBy('harga_dasar')->get()->map(fn($t) => [
            'tipe'     => $t,
            'total'    => $t->jumlahKamarJual(),
            'tersedia' => $t->tersedia($hariIni, $besok),
            'harga'    => $t->hargaPerMalam($hariIni),
        ]);

        // Okupansi 6 hari terakhir (dari night audit) + hari ini (live)
        $grafik = collect(range(6, 1))->map(function ($i) use ($jual) {
            $tgl = today()->subDays($i);
            $n   = NightAudit::whereDate('tanggal', $tgl)->value('jumlah_in_house');
            return ['label' => $tgl->format('d M'), 'persen' => ($n !== null && $jual > 0) ? round($n / $jual * 100) : 0];
        })->push(['label' => 'Hari ini', 'persen' => (int) round($stat['okupansi'])]);

        return view('dashboard', compact('stat', 'kedatangan', 'keberangkatan', 'statusKamar', 'ketersediaan', 'grafik'));
    }
}
