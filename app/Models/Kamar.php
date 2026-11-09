<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    protected $table    = 'kamar';
    protected $fillable = ['tipe_kamar_id','nomor','lantai','status_kamar','aktif','catatan'];
    protected $casts    = ['aktif' => 'boolean'];

    public function tipeKamar()        { return $this->belongsTo(TipeKamar::class); }
    public function reservasi()        { return $this->hasMany(Reservasi::class); }
    public function reservasiInHouse() { return $this->hasOne(Reservasi::class)->where('status', 'check_in'); }
    public function tasks()            { return $this->hasMany(HousekeepingTask::class); }
    public function kerusakan()        { return $this->hasMany(LaporanKerusakan::class); }
    public function logStatus()        { return $this->hasMany(StatusKamarLog::class)->latest(); }

    public function isTerisi(): bool
    {
        return $this->reservasiInHouse()->exists();
    }

    /** Ubah status kebersihan dan catat riwayatnya. */
    public function setStatus(string $ke, ?string $catatan = null, ?int $userId = null): void
    {
        $dari = $this->status_kamar;
        if ($dari === $ke) {
            return;
        }

        $this->update(['status_kamar' => $ke]);

        StatusKamarLog::create([
            'kamar_id' => $this->id,
            'user_id'  => $userId ?? auth()->id(),
            'dari'     => $dari,
            'ke'       => $ke,
            'catatan'  => $catatan,
        ]);
    }

    /** Apakah kamar ini bebas dari reservasi lain (yang sudah ditetapkan ke kamar ini) pada rentang malam tersebut. */
    public function tersediaUntuk($masuk, $keluar, ?int $abaikanReservasiId = null): bool
    {
        if (!$this->aktif || $this->status_kamar === 'rusak') {
            return false;
        }

        return !Reservasi::where('kamar_id', $this->id)
            ->aktif()
            ->overlap($masuk, $keluar)
            ->when($abaikanReservasiId, fn($q) => $q->where('id', '!=', $abaikanReservasiId))
            ->exists();
    }

    /** Kamar sejenis yang bersih, kosong, dan bebas dari reservasi lain mulai $mulai sampai tanggal keluar. */
    public static function siapUntuk(Reservasi $reservasi, $mulai)
    {
        return static::where('tipe_kamar_id', $reservasi->tipe_kamar_id)
            ->where('aktif', true)->where('status_kamar', 'siap')
            ->whereDoesntHave('reservasiInHouse')
            ->orderBy('nomor')->get()
            ->filter(fn($k) => $k->tersediaUntuk($mulai, $reservasi->tanggal_keluar, $reservasi->id))
            ->values();
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status_kamar) {
            'siap'        => ['label'=>'Siap',        'class'=>'bg-green-100 text-green-700',   'warna'=>'#10B981'],
            'kotor'       => ['label'=>'Kotor',       'class'=>'bg-red-100 text-red-700',       'warna'=>'#EF4444'],
            'dibersihkan' => ['label'=>'Dibersihkan', 'class'=>'bg-yellow-100 text-yellow-700', 'warna'=>'#F59E0B'],
            'rusak'       => ['label'=>'Rusak (OOO)', 'class'=>'bg-gray-200 text-gray-700',     'warna'=>'#6B7280'],
            default       => ['label'=>ucfirst($this->status_kamar), 'class'=>'bg-gray-100 text-gray-500', 'warna'=>'#9CA3AF'],
        };
    }
}
