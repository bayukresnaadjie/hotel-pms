<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Reservasi;
use App\Models\TipeKamar;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KetersediaanController extends Controller
{
    /** Kalender ketersediaan per tipe kamar + tape chart kamar (14 hari). */
    public function index(Request $request)
    {
        $mulai  = Carbon::parse($request->get('mulai', today()->toDateString()))->startOfDay();
        $jumlahHari = 14;
        $akhir  = $mulai->copy()->addDays($jumlahHari);
        $tanggal = collect(range(0, $jumlahHari - 1))->map(fn($i) => $mulai->copy()->addDays($i));

        $matriks = TipeKamar::where('aktif', true)->orderBy('harga_dasar')->get()->map(function ($t) use ($tanggal) {
            $total = $t->jumlahKamarJual();
            return [
                'tipe'  => $t,
                'total' => $total,
                'hari'  => $tanggal->map(function ($d) use ($t, $total) {
                    $terpakai = $t->jumlahTerpakai($d, $d->copy()->addDay());
                    return ['tersedia' => max(0, $total - $terpakai), 'terpakai' => $terpakai, 'harga' => $t->hargaPerMalam($d)];
                }),
            ];
        });

        // Tape chart: reservasi yang sudah punya kamar dalam jendela tanggal
        $reservasiKamar = Reservasi::aktif()->whereNotNull('kamar_id')->overlap($mulai, $akhir)
            ->with('tamu')->get()->groupBy('kamar_id');

        $kamar = Kamar::with('tipeKamar')->where('aktif', true)->orderBy('nomor')->get();

        return view('ketersediaan.index', compact('mulai', 'akhir', 'tanggal', 'jumlahHari', 'matriks', 'kamar', 'reservasiKamar'));
    }
}
