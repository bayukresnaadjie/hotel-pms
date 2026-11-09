<?php

namespace Database\Seeders;

use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Kamar;
use App\Models\Layanan;
use App\Models\NightAudit;
use App\Models\Pembayaran;
use App\Models\Reservasi;
use App\Models\Tamu;
use App\Models\TipeKamar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Semua tanggal relatif terhadap hari ini, jadi demo selalu terisi:
 * riwayat menginap, tamu in-house, kedatangan hari ini, dan booking mendatang.
 *
 * Format elemen daftar (satu per baris):
 *   [indeks tamu, kode tipe, offset hari masuk, jumlah malam, sumber, opsi]
 */
class ReservasiSeeder extends Seeder
{
    private $fd;
    private $finance;
    private $tipe;
    private $tamu;
    private $layanan;

    public function run(): void
    {
        $this->fd      = User::where('email', 'frontdesk@hotel.test')->value('id');
        $this->finance = User::where('email', 'finance@hotel.test')->value('id');
        $this->tipe    = TipeKamar::all()->keyBy('kode');
        $this->tamu    = Tamu::orderBy('id')->get()->values();
        $this->layanan = Layanan::all()->keyBy('kode');

        $hariIni = today();
        $d = fn (int $offset) => $hariIni->copy()->addDays($offset);

        // Sudah check-out (termasuk dua yang check-out hari ini)
        $riwayat = [
            [0,  'STD', -28, 2, 'walk_in', ['layanan' => [['SRP', 2, 0]], 'metode_bayar' => 'tunai']],
            [1,  'DLX', -26, 3, 'website', ['layanan' => [['SRP', 3, 1], ['LDY', 2, 1]], 'deposit' => 300000]],
            [2,  'SUI', -25, 2, 'telepon', ['layanan' => [['SPA', 2, 0], ['MLM', 2, 0]], 'metode_bayar' => 'kartu_kredit']],
            [3,  'STD', -22, 1, 'ota',     []],
            [4,  'DLX', -21, 2, 'ota',     ['layanan' => [['SRP', 2, 0]]]],
            [5,  'FAM', -19, 3, 'website', ['layanan' => [['EXB', 1, 0], ['SRP', 4, 1]], 'deposit' => 500000, 'dewasa' => 2, 'anak' => 2]],
            [6,  'DLX', -17, 4, 'ota',     ['layanan' => [['TRN', 1, 0], ['LDY', 3, 2]], 'diskon' => 100000, 'metode_bayar' => 'kartu_kredit']],
            [7,  'STD', -15, 2, 'website', ['layanan' => [['SRP', 2, 0]], 'metode_bayar' => 'qris']],
            [8,  'SUI', -13, 3, 'telepon', ['layanan' => [['SPA', 1, 1], ['MLM', 2, 0]], 'deposit' => 600000]],
            [9,  'STD', -12, 2, 'walk_in', ['metode_bayar' => 'tunai']],
            [11, 'DLX', -10, 2, 'website', ['layanan' => [['SRP', 2, 0], ['LCO', 1, 1]]]],
            [12, 'FAM', -8,  2, 'ota',     ['layanan' => [['SRP', 4, 0]], 'dewasa' => 2, 'anak' => 2]],
            [13, 'STD', -6,  3, 'ota',     ['layanan' => [['LDY', 2, 1]]]],
            [14, 'DLX', -5,  2, 'website', ['layanan' => [['SRP', 2, 0]], 'deposit' => 300000]],
            [4,  'STD', -3,  2, 'walk_in', ['metode_bayar' => 'tunai']],
            [1,  'STD', -2,  2, 'website', ['layanan' => [['SRP', 2, 0]]]],
            [9,  'DLX', -3,  3, 'ota',     ['layanan' => [['SRP', 2, 0], ['MNB', 2, 1]]]],
        ];

        // Sedang menginap (dua di antaranya check-out hari ini, dua check-in hari ini)
        $inhouse = [
            [2,  'SUI', -2, 2, 'telepon', ['layanan' => [['SPA', 1, 0], ['MLM', 2, 0]], 'deposit' => 500000]],
            [7,  'STD', -1, 1, 'website', ['layanan' => [['SRP', 2, 0]]]],
            [5,  'DLX', -1, 3, 'website', ['layanan' => [['SRP', 2, 0]], 'deposit' => 450000]],
            [6,  'STD', -2, 4, 'ota',     ['layanan' => [['LDY', 2, 1]]]],
            [3,  'FAM', -1, 2, 'walk_in', ['layanan' => [['EXB', 1, 0]], 'dewasa' => 2, 'anak' => 2]],
            [8,  'DLX', 0,  2, 'telepon', ['deposit' => 300000]],
            [0,  'STD', -3, 5, 'website', ['layanan' => [['SRP', 2, 0], ['SRP', 2, 1], ['LDY', 1, 2]]]],
            [14, 'SUI', 0,  1, 'website', []],
        ];

        // Akan datang hari ini (belum check-in)
        $kedatangan = [
            [13, 'STD', 0, 2, 'ota',     []],
            [4,  'DLX', 0, 3, 'website', ['deposit' => 450000, 'catatan' => 'Kamar lantai atas, bebas asap rokok']],
            [12, 'FAM', 0, 2, 'telepon', ['deposit' => 500000, 'dewasa' => 2, 'anak' => 2, 'catatan' => 'Butuh baby cot']],
            [11, 'SUI', 0, 2, 'telepon', ['deposit' => 1000000, 'catatan' => 'Kejutan ulang tahun: kue dan bunga']],
        ];

        // Booking mendatang. Tiga Executive Suite pada malam +3 s/d +5 sengaja dibuat habis (sold out)
        $mendatang = [
            [1,  'DLX', 1,  2, 'website', ['deposit' => 300000]],
            [0,  'STD', 3,  3, 'ota',     []],
            [15, 'FAM', 2,  2, 'website', ['deposit' => 500000, 'dewasa' => 3, 'anak' => 1]],
            [2,  'SUI', 3,  2, 'telepon', ['deposit' => 600000]],
            [11, 'SUI', 3,  2, 'website', ['deposit' => 600000]],
            [6,  'SUI', 3,  2, 'ota',     []],
            [4,  'STD', 5,  2, 'telepon', []],
            [7,  'DLX', 5,  3, 'website', ['deposit' => 450000]],
            [9,  'STD', 7,  2, 'ota',     []],
            [12, 'DLX', 7,  2, 'website', []],
            [13, 'FAM', 12, 4, 'website', ['deposit' => 600000, 'dewasa' => 2, 'anak' => 2]],
            [3,  'DLX', 41, 3, 'website', []],
            [5,  'SUI', 42, 2, 'ota',     []],
            [14, 'STD', 43, 4, 'website', []],
            [15, 'STD', 62, 2, 'ota',     []],
        ];

        foreach ($riwayat as [$t, $kode, $off, $malam, $sumber, $opt]) {
            $this->buat($t, $kode, $d($off), $d($off + $malam), 'check_out', $opt + ['sumber' => $sumber]);
        }
        foreach ($inhouse as [$t, $kode, $off, $malam, $sumber, $opt]) {
            $this->buat($t, $kode, $d($off), $d($off + $malam), 'check_in', $opt + ['sumber' => $sumber]);
        }
        foreach ($kedatangan as [$t, $kode, $off, $malam, $sumber, $opt]) {
            $this->buat($t, $kode, $d($off), $d($off + $malam), 'dikonfirmasi', $opt + ['sumber' => $sumber]);
        }
        foreach ($mendatang as [$t, $kode, $off, $malam, $sumber, $opt]) {
            $this->buat($t, $kode, $d($off), $d($off + $malam), 'dikonfirmasi', $opt + ['sumber' => $sumber]);
        }

        // Tidak datang (no-show) dan pembatalan
        $this->buat(15, 'STD', $d(-1), $d(1),  'no_show',    ['sumber' => 'website', 'dipesan' => 7]);
        $this->buat(5,  'DLX', $d(10), $d(12), 'dibatalkan', ['sumber' => 'website', 'alasan' => 'Perubahan rencana perjalanan']);
        $this->buat(3,  'STD', $d(-5), $d(-3), 'dibatalkan', ['sumber' => 'ota', 'alasan' => 'Tamu sakit, dibatalkan sesuai kebijakan']);

        $this->buatNightAudit();

        $this->command->info('✅ Reservasi: ' . Reservasi::count() . ' (check-out ' . Reservasi::where('status', 'check_out')->count()
            . ', in-house ' . Reservasi::where('status', 'check_in')->count()
            . ', dikonfirmasi ' . Reservasi::where('status', 'dikonfirmasi')->count() . ')');
    }

    private function buat(int $tamuIdx, string $kode, Carbon $masuk, Carbon $keluar, string $status, array $opt = []): ?Reservasi
    {
        $tipe    = $this->tipe[$kode];
        $tamu    = $this->tamu[$tamuIdx];
        $kamarId = null;

        if (in_array($status, ['check_in', 'check_out'], true)) {
            $kamar = $this->pilihKamar($tipe, $masuk, $keluar);
            if (!$kamar) {
                $this->command->warn("Tidak ada kamar {$kode} kosong untuk {$masuk->toDateString()}, reservasi dilewati");
                return null;
            }
            $kamarId = $kamar->id;
        }

        if (in_array($status, ['dikonfirmasi', 'check_in'], true) && $tipe->tersedia($masuk, $keluar) < 1) {
            $this->command->warn("Stok {$kode} habis untuk {$masuk->toDateString()}, reservasi dilewati");
            return null;
        }

        $deposit = (float) ($opt['deposit'] ?? 0);
        $sumber  = $opt['sumber'] ?? 'walk_in';
        $dipesan = $masuk->copy()->subDays($opt['dipesan'] ?? ($sumber === 'walk_in' ? 0 : 3));
        if ($dipesan->gt(today())) {
            $dipesan = today()->copy();
        }

        $r = Reservasi::create([
            'tamu_id'           => $tamu->id,
            'tipe_kamar_id'     => $tipe->id,
            'kamar_id'          => $kamarId,
            'tanggal_masuk'     => $masuk->toDateString(),
            'tanggal_keluar'    => $keluar->toDateString(),
            'jumlah_dewasa'     => $opt['dewasa'] ?? 2,
            'jumlah_anak'       => $opt['anak'] ?? 0,
            'sumber'            => $sumber,
            'status'            => $status,
            'total_kamar'       => $tipe->totalTarif($masuk, $keluar),
            'deposit'           => $deposit,
            'permintaan_khusus' => $opt['catatan'] ?? null,
            'dibuat_oleh'       => $this->fd,
            'check_in_at'       => in_array($status, ['check_in', 'check_out'], true) ? $masuk->copy()->setTime(14, 30) : null,
            'check_out_at'      => $status === 'check_out' ? $keluar->copy()->setTime(11, 15) : null,
            'dibatalkan_at'     => $status === 'dibatalkan' ? $dipesan->copy()->addDay() : null,
            'alasan_batal'      => $status === 'dibatalkan' ? ($opt['alasan'] ?? null) : null,
        ]);
        $r->forceFill(['created_at' => $dipesan, 'updated_at' => $dipesan])->saveQuietly();

        if (in_array($status, ['dibatalkan', 'no_show'], true)) {
            return $r;
        }

        // ── Folio, deposit, dan posting biaya ─────────────────────────────────
        $folio = Folio::untuk($r);

        if ($deposit > 0) {
            $waktu = $masuk->copy()->subDays(2);
            if ($waktu->gt(today())) {
                $waktu = today()->copy();
            }
            $this->bayar($folio, 'deposit', 'transfer', $deposit, $waktu);
        }

        if (in_array($status, ['check_in', 'check_out'], true)) {
            // Biaya kamar diposting per malam oleh night audit: sampai kemarin untuk tamu in-house
            $akhirMalam = $status === 'check_out' ? $keluar->copy()->subDay() : today()->copy()->subDay();
            $tgl = $masuk->copy();
            while ($tgl->lte($akhirMalam) && $tgl->lt($keluar)) {
                $folio->postingKamar($r, $tgl, $this->fd);
                $tgl->addDay();
            }

            foreach ($opt['layanan'] ?? [] as [$kodeLayanan, $qty, $offset]) {
                $tglItem = $masuk->copy()->addDays($offset);
                if ($tglItem->gt(today())) {
                    continue;
                }
                $ly = $this->layanan[$kodeLayanan];
                FolioItem::create([
                    'folio_id' => $folio->id, 'tanggal' => $tglItem->toDateString(), 'tipe' => 'layanan',
                    'layanan_id' => $ly->id, 'deskripsi' => $ly->nama, 'qty' => $qty,
                    'harga_satuan' => (float) $ly->harga, 'jumlah' => (float) $ly->harga * $qty, 'dibuat_oleh' => $this->fd,
                ]);
            }

            if (!empty($opt['diskon'])) {
                FolioItem::create([
                    'folio_id' => $folio->id, 'tanggal' => $masuk->toDateString(), 'tipe' => 'diskon',
                    'deskripsi' => 'Kompensasi keterlambatan layanan', 'qty' => 1,
                    'harga_satuan' => -1 * (float) $opt['diskon'], 'jumlah' => -1 * (float) $opt['diskon'], 'dibuat_oleh' => $this->fd,
                ]);
            }
        }

        // ── Tamu yang sudah check-out: lunasi sisa tagihan, lalu tutup folio ───
        if ($status === 'check_out') {
            $folio->refresh();
            $sisa = (float) $folio->total - (float) $folio->terbayar;
            if ($sisa > 0) {
                $this->bayar($folio, 'pembayaran', $opt['metode_bayar'] ?? 'kartu_debit', $sisa, $keluar->copy()->setTime(11, 10));
            }
            $folio->refresh();
            $folio->update(['status' => 'tertutup', 'ditutup_at' => $keluar->copy()->setTime(11, 15)]);
        }

        return $r;
    }

    private function bayar(Folio $folio, string $jenis, string $metode, float $jumlah, Carbon $waktu): void
    {
        $p = new Pembayaran([
            'folio_id' => $folio->id, 'jenis' => $jenis, 'metode' => $metode,
            'jumlah' => $jumlah, 'diterima_oleh' => $this->fd,
        ]);
        $p->created_at = $waktu;
        $p->save();
    }

    /** Kamar pertama (berurutan nomor) tipe ini yang bebas pada rentang malam tersebut. */
    private function pilihKamar(TipeKamar $tipe, Carbon $masuk, Carbon $keluar): ?Kamar
    {
        $kamarList = Kamar::where('tipe_kamar_id', $tipe->id)
            ->where('aktif', true)->where('status_kamar', '!=', 'rusak')
            ->orderBy('nomor')->get();

        foreach ($kamarList as $k) {
            $bentrok = Reservasi::where('kamar_id', $k->id)
                ->whereIn('status', ['dikonfirmasi', 'check_in', 'check_out'])
                ->overlap($masuk, $keluar)->exists();

            // Untuk tamu yang masuk hari ini, jangan pakai kamar yang baru/akan ditinggalkan tamu lain hari ini (belum dibersihkan)
            if (!$bentrok && $masuk->isSameDay(today())) {
                $bentrok = Reservasi::where('kamar_id', $k->id)
                    ->whereIn('status', ['check_in', 'check_out'])
                    ->whereDate('tanggal_keluar', today())->exists();
            }

            if (!$bentrok) {
                return $k;
            }
        }

        return null;
    }

    /** Catat night audit harian dari biaya kamar yang sudah diposting. */
    private function buatNightAudit(): void
    {
        $pertama = FolioItem::where('tipe', 'kamar')->min('tanggal');
        if (!$pertama) {
            return;
        }

        $jual = Kamar::where('aktif', true)->where('status_kamar', '!=', 'rusak')->count();
        $tgl  = Carbon::parse($pertama);

        while ($tgl->lt(today())) {
            $n   = FolioItem::where('tipe', 'kamar')->whereDate('tanggal', $tgl->toDateString())->count();
            $sum = (float) FolioItem::where('tipe', 'kamar')->whereDate('tanggal', $tgl->toDateString())->sum('jumlah');

            NightAudit::create([
                'tanggal'                => $tgl->toDateString(),
                'dijalankan_oleh'        => $this->finance,
                'jumlah_in_house'        => $n,
                'jumlah_diposting'       => $n,
                'total_pendapatan_kamar' => $sum,
                'okupansi'               => $jual > 0 ? round($n / $jual * 100, 2) : 0,
            ]);
            $tgl->addDay();
        }
    }
}
