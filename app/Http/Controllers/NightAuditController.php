<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Kamar;
use App\Models\NightAudit;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NightAuditController extends Controller
{
    public function index()
    {
        $hariIni    = today();
        $sudahAudit = NightAudit::whereDate('tanggal', $hariIni)->first();

        $inHouse = Reservasi::where('status', 'check_in')
            ->whereDate('tanggal_masuk', '<=', $hariIni)->whereDate('tanggal_keluar', '>', $hariIni)
            ->with(['tamu', 'kamar', 'tipeKamar', 'folio.items'])->orderBy('kamar_id')->get();

        $preview = $inHouse->map(function ($r) use ($hariIni) {
            $sudah = $r->folio && $r->folio->items->where('tipe', 'kamar')->contains(fn($i) => $i->tanggal->isSameDay($hariIni));
            return ['reservasi' => $r, 'harga' => $r->tipeKamar->hargaPerMalam($hariIni), 'sudah' => $sudah];
        });

        $akanDiposting = (float) $preview->where('sudah', false)->sum('harga');

        // Tamu yang seharusnya sudah datang tetapi belum check-in: perlu diputuskan no-show atau ditunggu
        $belumDatang = Reservasi::where('status', 'dikonfirmasi')->whereDate('tanggal_masuk', '<=', $hariIni)
            ->with(['tamu', 'tipeKamar'])->orderBy('tanggal_masuk')->get();

        $jual    = Kamar::where('aktif', true)->where('status_kamar', '!=', 'rusak')->count();
        $riwayat = NightAudit::with('dijalankanOleh')->orderByDesc('tanggal')->paginate(15);

        return view('nightaudit.index', compact('hariIni', 'sudahAudit', 'preview', 'akanDiposting', 'belumDatang', 'jual', 'riwayat'));
    }

    /** Posting biaya kamar malam ini ke folio semua tamu in-house, lalu tutup hari. */
    public function run(Request $request)
    {
        $tgl = today();
        $request->validate(['catatan' => 'nullable|string|max:150']);

        if (NightAudit::whereDate('tanggal', $tgl)->exists()) {
            return back()->with('error', 'Night audit untuk hari ini sudah dijalankan.');
        }

        $hasil = DB::transaction(function () use ($tgl, $request) {
            $inHouse = Reservasi::where('status', 'check_in')
                ->whereDate('tanggal_masuk', '<=', $tgl)->whereDate('tanggal_keluar', '>', $tgl)
                ->with(['tipeKamar', 'kamar'])->get();

            $diposting = 0;
            foreach ($inHouse as $r) {
                $folio = Folio::untuk($r);
                if ($folio->postingKamar($r, $tgl, auth()->id())) {
                    $diposting++;
                }
            }

            $pendapatan = (float) FolioItem::where('tipe', 'kamar')->whereDate('tanggal', $tgl)->sum('jumlah');
            $jual       = Kamar::where('aktif', true)->where('status_kamar', '!=', 'rusak')->count();

            NightAudit::create([
                'tanggal'                => $tgl->toDateString(),
                'dijalankan_oleh'        => auth()->id(),
                'jumlah_in_house'        => $inHouse->count(),
                'jumlah_diposting'       => $diposting,
                'total_pendapatan_kamar' => $pendapatan,
                'okupansi'               => $jual > 0 ? round($inHouse->count() / $jual * 100, 2) : 0,
                'catatan'                => $request->catatan,
            ]);

            return ['diposting' => $diposting, 'pendapatan' => $pendapatan];
        });

        return back()->with('success', "Night audit selesai: {$hasil['diposting']} folio diposting, pendapatan kamar Rp " . number_format($hasil['pendapatan'], 0, ',', '.') . '.');
    }
}
