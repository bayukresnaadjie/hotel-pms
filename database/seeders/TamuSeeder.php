<?php

namespace Database\Seeders;

use App\Models\Tamu;
use Illuminate\Database\Seeder;

class TamuSeeder extends Seeder
{
    /**
     * Urutan array ini dipakai ReservasiSeeder sebagai indeks (0..15). Jangan diubah urutannya.
     * Indeks 10 = blacklist (tidak dipakai untuk reservasi).
     */
    public function run(): void
    {
        $data = [
            // nama, email, telepon, jenis, nomor identitas, kota, negara, tipe, catatan
            ['Budi Santoso',    'budi.santoso@mail.test',  '081234560001', 'ktp',    '3471010101900001', 'Jakarta',    'Indonesia', 'reguler',   null],
            ['Siti Aminah',     'siti.aminah@mail.test',   '081234560002', 'ktp',    '3273020202910002', 'Bandung',    'Indonesia', 'reguler',   null],
            ['Andi Wijaya',     'andi.wijaya@mail.test',   '081234560003', 'ktp',    '3578030303850003', 'Surabaya',   'Indonesia', 'vip',       'Tamu langganan, suka kamar lantai atas'],
            ['Dewi Lestari',    'dewi.lestari@mail.test',  '081234560004', 'ktp',    '3404040404920004', 'Yogyakarta', 'Indonesia', 'reguler',   null],
            ['Rudi Hartono',    'rudi.hartono@mail.test',  '081234560005', 'ktp',    '3374050505880005', 'Semarang',   'Indonesia', 'reguler',   null],
            ['Maya Putri',      'maya.putri@mail.test',    '081234560006', 'ktp',    '1271060606950006', 'Medan',      'Indonesia', 'reguler',   null],
            ['John Smith',      'john.smith@mail.test',    '0411223344',   'paspor', 'N1234567',         'Sydney',     'Australia', 'reguler',   'Alergi kacang'],
            ['Yuki Tanaka',     'yuki.tanaka@mail.test',   '0312345678',   'paspor', 'TK9876543',        'Tokyo',      'Jepang',    'reguler',   null],
            ['Hendra Gunawan',  'hendra.g@mail.test',      '081234560009', 'ktp',    '5171090909870009', 'Denpasar',   'Indonesia', 'reguler',   null],
            ['Rina Kartika',    'rina.kartika@mail.test',  '081234560010', 'ktp',    '3507101010940010', 'Malang',     'Indonesia', 'reguler',   null],
            ['Fajar Nugroho',   'fajar.n@mail.test',       '081234560011', 'ktp',    '3372111111890011', 'Solo',       'Indonesia', 'blacklist', 'Meninggalkan hotel tanpa melunasi tagihan'],
            ['Lisa Marlina',    'lisa.marlina@mail.test',  '081234560012', 'ktp',    '3174121212820012', 'Jakarta',    'Indonesia', 'vip',       'Pelanggan korporat, minta invoice atas nama perusahaan'],
            ['Agus Prasetyo',   'agus.p@mail.test',        '081234560013', 'ktp',    '7371130313900013', 'Makassar',   'Indonesia', 'reguler',   null],
            ['Nadia Rahma',     'nadia.rahma@mail.test',   '081234560014', 'ktp',    '1371140414960014', 'Padang',     'Indonesia', 'reguler',   null],
            ['Bambang Susilo',  'bambang.s@mail.test',     '081234560015', 'ktp',    '1671151515830015', 'Palembang',  'Indonesia', 'reguler',   null],
            ['Citra Dewanti',   'citra.dewanti@mail.test', '081234560016', 'ktp',    '5171161616970016', 'Denpasar',   'Indonesia', 'reguler',   null],
        ];

        foreach ($data as [$nama, $email, $telp, $jenis, $nomor, $kota, $negara, $tipe, $catatan]) {
            Tamu::create([
                'nama' => $nama, 'email' => $email, 'telepon' => $telp, 'jenis_identitas' => $jenis,
                'nomor_identitas' => $nomor, 'kota' => $kota, 'negara' => $negara, 'tipe' => $tipe, 'catatan' => $catatan,
            ]);
        }

        $this->command->info('✅ ' . count($data) . ' tamu dibuat');
    }
}
