<?php

namespace App\Http\Controllers;

use App\Models\FolioItem;
use App\Models\Kamar;
use App\Models\Reservasi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $sampai = Carbon::parse($request->get('sampai', today()->subDay()->toDateString()))->startOfDay();
        $dari   = Carbon::parse($request->get('dari', $sampai->copy()->subDays(29)->toDateString()))->startOfDay();
        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        if ($dari->lt($sampai->copy()->subDays(120))) {
            $dari = $sampai->copy()->subDays(120);
        }

        $jual = Kamar::where('aktif', true)->where('status_kamar', '!=', 'rusak')->count();
        $batas = [$dari->toDateString(), $sampai->toDateString()];

        // ── Per hari: kamar terjual, pendapatan, okupansi, ADR, RevPAR ────────
        $baris = FolioItem::where('tipe', 'kamar')->whereBetween('tanggal', $batas)
            ->selectRaw('tanggal, COUNT(*) as terjual, SUM(jumlah) as pendapatan')->groupBy('tanggal')->get()
            ->keyBy(fn($r) => Carbon::parse($r->tanggal)->toDateString());

        $harian = collect();
        for ($t = $dari->copy(); $t->lte($sampai); $t->addDay()) {
            $row        = $baris->get($t->toDateString());
            $terjual    = $row ? (int) $row->terjual : 0;
            $pendapatan = $row ? (float) $row->pendapatan : 0.0;
            $harian->push([
                'tanggal'    => $t->copy(),
                'terjual'    => $terjual,
                'pendapatan' => $pendapatan,
                'okupansi'   => $jual > 0 ? round($terjual / $jual * 100, 1) : 0,
                'adr'        => $terjual > 0 ? $pendapatan / $terjual : 0,
                'revpar'     => $jual > 0 ? $pendapatan / $jual : 0,
            ]);
        }

        $jumlahHari      = max(1, $harian->count());
        $totalTerjual    = (int) $harian->sum('terjual');
        $totalPendapatan = (float) $harian->sum('pendapatan');

        $ringkas = [
            'pendapatan_kamar' => $totalPendapatan,
            'malam_terjual'    => $totalTerjual,
            'okupansi'         => $jual > 0 ? round($totalTerjual / ($jual * $jumlahHari) * 100, 1) : 0,
            'adr'              => $totalTerjual > 0 ? $totalPendapatan / $totalTerjual : 0,
            'revpar'           => $jual > 0 ? $totalPendapatan / ($jual * $jumlahHari) : 0,
            'pendapatan_lain'  => (float) FolioItem::whereIn('tipe', ['layanan', 'lainnya'])->whereBetween('tanggal', $batas)->sum('jumlah'),
            'diskon'           => abs((float) FolioItem::where('tipe', 'diskon')->whereBetween('tanggal', $batas)->sum('jumlah')),
            'hari'             => $jumlahHari,
        ];

        // ── Per tipe kamar ────────────────────────────────────────────────────
        $perTipe = FolioItem::where('folio_item.tipe', 'kamar')->whereBetween('folio_item.tanggal', $batas)
            ->join('folio', 'folio.id', '=', 'folio_item.folio_id')
            ->join('reservasi', 'reservasi.id', '=', 'folio.reservasi_id')
            ->join('tipe_kamar', 'tipe_kamar.id', '=', 'reservasi.tipe_kamar_id')
            ->selectRaw('tipe_kamar.nama as nama, COUNT(*) as malam, SUM(folio_item.jumlah) as pendapatan')
            ->groupBy('tipe_kamar.nama')->orderByDesc('pendapatan')->get();

        // ── Per sumber reservasi (tamu yang menginap pada periode) ────────────
        $perSumber = Reservasi::whereIn('status', ['check_in', 'check_out'])->whereBetween('tanggal_masuk', $batas)
            ->selectRaw('sumber, COUNT(*) as total, SUM(total_kamar) as nilai')->groupBy('sumber')->orderByDesc('total')->get();

        // ── Layanan terlaris ──────────────────────────────────────────────────
        $perLayanan = FolioItem::where('folio_item.tipe', 'layanan')->whereBetween('folio_item.tanggal', $batas)
            ->join('layanan', 'layanan.id', '=', 'folio_item.layanan_id')
            ->selectRaw('layanan.nama as nama, SUM(folio_item.qty) as qty, SUM(folio_item.jumlah) as pendapatan')
            ->groupBy('layanan.nama')->orderByDesc('pendapatan')->limit(6)->get();

        // ── Proyeksi 14 malam ke depan dari reservasi aktif ───────────────────
        $proyeksi = collect(range(0, 13))->map(function ($i) use ($jual) {
            $tgl = today()->copy()->addDays($i);
            $n = Reservasi::aktif()->whereDate('tanggal_masuk', '<=', $tgl)->whereDate('tanggal_keluar', '>', $tgl)->count();
            return [
                'tanggal'    => $tgl,
                'terisi'     => $n,
                'okupansi'   => $jual > 0 ? round($n / $jual * 100) : 0,
                'kedatangan' => Reservasi::where('status', 'dikonfirmasi')->whereDate('tanggal_masuk', $tgl)->count(),
            ];
        });

        return view('laporan.index', compact('dari', 'sampai', 'harian', 'ringkas', 'perTipe', 'perSumber', 'perLayanan', 'proyeksi', 'jual'));
    }
}
