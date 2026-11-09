<?php

namespace Database\Seeders;

use App\Models\HousekeepingTask;
use App\Models\Kamar;
use App\Models\LaporanKerusakan;
use App\Models\Reservasi;
use App\Models\StatusKamarLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class HousekeepingSeeder extends Seeder
{
    public function run(): void
    {
        $fd  = User::where('email', 'frontdesk@hotel.test')->value('id');
        $hk1 = User::where('email', 'hk1@hotel.test')->value('id');
        $hk2 = User::where('email', 'hk2@hotel.test')->value('id');
        $hariIni = today();

        // 1. Kamar yang tamunya sudah check-out hari ini → kotor / sedang dibersihkan
        $urutan = 0;
        $sudahCheckout = Reservasi::where('status', 'check_out')->whereDate('tanggal_keluar', $hariIni)->orderBy('id')->get();
        foreach ($sudahCheckout as $r) {
            $kamar = Kamar::find($r->kamar_id);
            if (!$kamar || Reservasi::where('kamar_id', $kamar->id)->where('status', 'check_in')->exists()) {
                continue;
            }

            if ($urutan === 0) {
                $kamar->setStatus('kotor', 'Check-out ' . $r->kode_booking, $fd);
                HousekeepingTask::create([
                    'kamar_id' => $kamar->id, 'tipe' => 'checkout_clean', 'status' => 'menunggu',
                    'tanggal' => $hariIni->toDateString(), 'catatan' => 'Check-out ' . $r->kode_booking,
                ]);
            } else {
                $kamar->setStatus('dibersihkan', 'Sedang dibersihkan', $hk1);
                HousekeepingTask::create([
                    'kamar_id' => $kamar->id, 'ditugaskan_ke' => $hk1, 'tipe' => 'checkout_clean', 'status' => 'dikerjakan',
                    'tanggal' => $hariIni->toDateString(), 'mulai_at' => now()->subMinutes(20), 'catatan' => 'Check-out ' . $r->kode_booking,
                ]);
            }
            $urutan++;
        }

        // 2. Tamu in-house yang check-out hari ini: tugas menunggu tamu keluar
        $berangkatHariIni = Reservasi::where('status', 'check_in')->whereDate('tanggal_keluar', $hariIni)->get();
        foreach ($berangkatHariIni as $r) {
            HousekeepingTask::create([
                'kamar_id' => $r->kamar_id, 'tipe' => 'checkout_clean', 'status' => 'menunggu',
                'tanggal' => $hariIni->toDateString(), 'catatan' => 'Menunggu tamu check-out',
            ]);
        }

        // 3. Tamu in-house yang menginap lebih lama: pembersihan stayover (sebagian sudah selesai)
        $stayover = Reservasi::where('status', 'check_in')
            ->whereDate('tanggal_masuk', '<', $hariIni)->whereDate('tanggal_keluar', '>', $hariIni)->orderBy('id')->get();
        foreach ($stayover as $i => $r) {
            $selesai = $i % 2 === 0;
            HousekeepingTask::create([
                'kamar_id' => $r->kamar_id, 'ditugaskan_ke' => $hk2, 'tipe' => 'stayover',
                'status' => $selesai ? 'selesai' : 'menunggu', 'tanggal' => $hariIni->toDateString(),
                'mulai_at' => $selesai ? now()->subHours(3) : null, 'selesai_at' => $selesai ? now()->subHours(2) : null,
            ]);
        }

        // 4. Riwayat: kamar dari tamu yang check-out kemarin sudah dibersihkan
        $kemarin = Reservasi::where('status', 'check_out')->whereDate('tanggal_keluar', $hariIni->copy()->subDay())->get();
        foreach ($kemarin as $i => $r) {
            HousekeepingTask::create([
                'kamar_id' => $r->kamar_id, 'ditugaskan_ke' => $i % 2 === 0 ? $hk1 : $hk2, 'tipe' => 'checkout_clean',
                'status' => 'selesai', 'tanggal' => $hariIni->copy()->subDay()->toDateString(),
                'mulai_at' => now()->subDay()->setTime(12, 30), 'selesai_at' => now()->subDay()->setTime(13, 15),
            ]);
        }

        // 5. Laporan kerusakan
        $k105 = Kamar::where('nomor', '105')->first();
        if ($k105) {
            StatusKamarLog::create(['kamar_id' => $k105->id, 'user_id' => $hk1, 'dari' => 'siap', 'ke' => 'rusak', 'catatan' => 'AC tidak dingin']);
            LaporanKerusakan::create([
                'kamar_id' => $k105->id, 'dilaporkan_oleh' => $hk1, 'judul' => 'AC tidak dingin',
                'deskripsi' => 'Kompresor AC bunyi keras dan tidak mendinginkan. Teknisi sedang memesan suku cadang.',
                'prioritas' => 'tinggi', 'status' => 'diproses', 'kamar_tidak_bisa_dipakai' => true,
            ]);
        }

        $k203 = Kamar::where('nomor', '203')->first();
        if ($k203) {
            LaporanKerusakan::create([
                'kamar_id' => $k203->id, 'dilaporkan_oleh' => $hk2, 'judul' => 'Keran wastafel bocor',
                'prioritas' => 'sedang', 'status' => 'selesai', 'kamar_tidak_bisa_dipakai' => false,
                'diselesaikan_at' => now()->subDays(2),
            ]);
        }

        $k304 = Kamar::where('nomor', '304')->first();
        if ($k304) {
            LaporanKerusakan::create([
                'kamar_id' => $k304->id, 'dilaporkan_oleh' => $hk2, 'judul' => 'Lampu kamar mandi mati',
                'prioritas' => 'rendah', 'status' => 'dilaporkan', 'kamar_tidak_bisa_dipakai' => false,
            ]);
        }

        $this->command->info('✅ Housekeeping: ' . HousekeepingTask::count() . ' tugas, ' . LaporanKerusakan::count() . ' laporan kerusakan');
    }
}
