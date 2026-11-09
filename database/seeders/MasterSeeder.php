<?php

namespace Database\Seeders;

use App\Models\Fasilitas;
use App\Models\Kamar;
use App\Models\Layanan;
use App\Models\TarifMusiman;
use App\Models\TipeKamar;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // ── Fasilitas ─────────────────────────────────────────────────────────
        $daftarFasilitas = [
            ['AC', '❄️'], ['WiFi Gratis', '📶'], ['TV LED 42"', '📺'], ['Air Panas', '🚿'],
            ['Mini Bar', '🍹'], ['Coffee Maker', '☕'], ['Safe Box', '🔐'], ['Bathtub', '🛁'],
            ['Balkon', '🌇'], ['Ruang Tamu', '🛋️'],
        ];
        $fasilitas = [];
        foreach ($daftarFasilitas as [$nama, $icon]) {
            $fasilitas[$nama] = Fasilitas::create(['nama' => $nama, 'icon' => $icon]);
        }

        // ── Tipe kamar ────────────────────────────────────────────────────────
        $dasar = ['AC', 'WiFi Gratis', 'TV LED 42"', 'Air Panas'];
        $tipeData = [
            ['STD', 'Standard Room',   450000,  'Double Bed',        24, 2, 1, 'Kamar nyaman untuk perjalanan singkat dengan semua kebutuhan dasar.', $dasar],
            ['DLX', 'Deluxe Room',     650000,  'Queen Bed',         32, 2, 1, 'Kamar lebih luas dengan mini bar dan coffee maker.', array_merge($dasar, ['Mini Bar', 'Coffee Maker', 'Safe Box'])],
            ['FAM', 'Family Room',     850000,  '1 King + 1 Single', 44, 4, 2, 'Cocok untuk keluarga, dilengkapi balkon dan tempat tidur tambahan.', array_merge($dasar, ['Mini Bar', 'Safe Box', 'Balkon'])],
            ['SUI', 'Executive Suite', 1500000, 'King Bed',          65, 2, 2, 'Suite dengan ruang tamu terpisah, bathtub, dan pemandangan kota.', array_merge($dasar, ['Mini Bar', 'Coffee Maker', 'Safe Box', 'Bathtub', 'Balkon', 'Ruang Tamu'])],
        ];

        $tipe = [];
        foreach ($tipeData as [$kode, $nama, $harga, $kasur, $luas, $dewasa, $anak, $desk, $fasList]) {
            $tipe[$kode] = TipeKamar::create([
                'kode' => $kode, 'nama' => $nama, 'harga_dasar' => $harga, 'jenis_kasur' => $kasur,
                'luas_m2' => $luas, 'kapasitas_dewasa' => $dewasa, 'kapasitas_anak' => $anak, 'deskripsi' => $desk,
            ]);
            foreach ($fasList as $f) {
                $tipe[$kode]->fasilitas()->attach($fasilitas[$f]->id);
            }
        }

        // ── Kamar (22 kamar) ──────────────────────────────────────────────────
        // STD 101-108 (lt.1) · DLX 201-208 (lt.2) · FAM 301-303 & SUI 304-306 (lt.3)
        $denah = [
            ['STD', 1, ['101', '102', '103', '104', '105', '106', '107', '108']],
            ['DLX', 2, ['201', '202', '203', '204', '205', '206', '207', '208']],
            ['FAM', 3, ['301', '302', '303']],
            ['SUI', 3, ['304', '305', '306']],
        ];
        foreach ($denah as [$kode, $lantai, $nomorList]) {
            foreach ($nomorList as $nomor) {
                $rusak = $nomor === '105';
                Kamar::create([
                    'tipe_kamar_id' => $tipe[$kode]->id,
                    'nomor'         => $nomor,
                    'lantai'        => $lantai,
                    'status_kamar'  => $rusak ? 'rusak' : 'siap',
                    'catatan'       => $rusak ? 'AC tidak dingin, menunggu teknisi' : null,
                ]);
            }
        }

        // ── Layanan tambahan ──────────────────────────────────────────────────
        $layanan = [
            ['SRP', 'Sarapan Buffet',            'fnb',       75000],
            ['MLM', 'Makan Malam Set Menu',      'fnb',       120000],
            ['MNB', 'Mini Bar - Minuman',        'fnb',       25000],
            ['LDY', 'Laundry (per set)',         'laundry',   35000],
            ['SPA', 'Pijat Relaksasi 60 menit',  'spa',       250000],
            ['TRN', 'Antar Jemput Bandara',      'transport', 300000],
            ['EXB', 'Extra Bed',                 'lainnya',   150000],
            ['LCO', 'Late Check-out (s/d 16.00)', 'lainnya',  200000],
        ];
        foreach ($layanan as [$kode, $nama, $kategori, $harga]) {
            Layanan::create(['kode' => $kode, 'nama' => $nama, 'kategori' => $kategori, 'harga' => $harga]);
        }

        // ── Tarif musiman (tanggal relatif terhadap hari ini) ──────────────────
        TarifMusiman::create([
            'nama' => 'High Season - Libur Panjang',
            'tanggal_mulai' => now()->addDays(40)->toDateString(), 'tanggal_selesai' => now()->addDays(47)->toDateString(),
            'pengali' => 1.50,
        ]);
        TarifMusiman::create([
            'nama' => 'Promo Low Season',
            'tanggal_mulai' => now()->addDays(60)->toDateString(), 'tanggal_selesai' => now()->addDays(90)->toDateString(),
            'pengali' => 0.90,
        ]);

        $this->command->info('✅ Master: 4 tipe, 22 kamar, ' . count($layanan) . ' layanan, 2 tarif musiman');
    }
}
