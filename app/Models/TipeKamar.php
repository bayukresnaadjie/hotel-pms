<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TipeKamar extends Model
{
    protected $table    = 'tipe_kamar';
    protected $fillable = ['kode','nama','deskripsi','kapasitas_dewasa','kapasitas_anak','jenis_kasur','luas_m2','harga_dasar','aktif'];
    protected $casts    = ['harga_dasar' => 'decimal:2', 'aktif' => 'boolean'];

    public function kamar()        { return $this->hasMany(Kamar::class); }
    public function fasilitas()    { return $this->belongsToMany(Fasilitas::class, 'fasilitas_tipe_kamar'); }
    public function reservasi()    { return $this->hasMany(Reservasi::class); }
    public function tarifMusiman() { return $this->hasMany(TarifMusiman::class); }

    /** Jumlah kamar yang boleh dijual: aktif dan tidak rusak (kotor tetap bisa dijual untuk tanggal mendatang). */
    public function jumlahKamarJual(): int
    {
        return $this->kamar()->where('aktif', true)->where('status_kamar', '!=', 'rusak')->count();
    }

    /** Jumlah reservasi aktif tipe ini yang menimpa rentang malam [masuk, keluar). */
    public function jumlahTerpakai($masuk, $keluar, ?int $abaikanReservasiId = null): int
    {
        return Reservasi::where('tipe_kamar_id', $this->id)
            ->aktif()
            ->overlap($masuk, $keluar)
            ->when($abaikanReservasiId, fn($q) => $q->where('id', '!=', $abaikanReservasiId))
            ->count();
    }

    /** Sisa kamar untuk rentang malam. Tamu check-out hari X tidak menghalangi tamu check-in hari X. */
    public function tersedia($masuk, $keluar, ?int $abaikanReservasiId = null): int
    {
        return max(0, $this->jumlahKamarJual() - $this->jumlahTerpakai($masuk, $keluar, $abaikanReservasiId));
    }

    /** Pengali musiman tertinggi yang berlaku pada tanggal tersebut (1.0 jika tidak ada). */
    public function pengaliMusiman($tanggal): float
    {
        $pengali = TarifMusiman::berlakuPada($tanggal, $this->id)->max('pengali');
        return $pengali ? (float) $pengali : 1.0;
    }

    /** Harga untuk satu malam yang dimulai pada $tanggal: harga dasar x musiman x akhir pekan, dibulatkan ke ribuan. */
    public function hargaPerMalam($tanggal): float
    {
        $tanggal = Carbon::parse($tanggal)->startOfDay();
        $harga   = (float) $this->harga_dasar * $this->pengaliMusiman($tanggal);

        if (in_array($tanggal->dayOfWeek, config('hotel.weekend_days', [5, 6]), true)) {
            $harga *= (float) config('hotel.weekend_pengali', 1.2);
        }

        return round($harga / 1000) * 1000;
    }

    /** Daftar harga per malam: [['tanggal' => Carbon, 'harga' => float], ...] */
    public function rincianTarif($masuk, $keluar): array
    {
        $rincian = [];
        $tgl     = Carbon::parse($masuk)->startOfDay();
        $akhir   = Carbon::parse($keluar)->startOfDay();

        while ($tgl->lt($akhir)) {
            $rincian[] = ['tanggal' => $tgl->copy(), 'harga' => $this->hargaPerMalam($tgl)];
            $tgl->addDay();
        }

        return $rincian;
    }

    public function totalTarif($masuk, $keluar): float
    {
        return (float) array_sum(array_column($this->rincianTarif($masuk, $keluar), 'harga'));
    }

    public function getHargaDasarFormatAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->harga_dasar, 0, ',', '.');
    }
}
