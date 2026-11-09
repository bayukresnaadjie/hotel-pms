<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reservasi extends Model
{
    protected $table    = 'reservasi';
    protected $fillable = [
        'kode_booking','tamu_id','tipe_kamar_id','kamar_id',
        'tanggal_masuk','tanggal_keluar','jumlah_malam','jumlah_dewasa','jumlah_anak',
        'sumber','status','total_kamar','deposit','permintaan_khusus','dibuat_oleh',
        'check_in_at','check_out_at','dibatalkan_at','alasan_batal',
    ];
    protected $casts = [
        'tanggal_masuk'  => 'date',
        'tanggal_keluar' => 'date',
        'total_kamar'    => 'decimal:2',
        'deposit'        => 'decimal:2',
        'check_in_at'    => 'datetime',
        'check_out_at'   => 'datetime',
        'dibatalkan_at'  => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($r) {
            if (empty($r->kode_booking)) {
                do {
                    $kode = 'BK-' . strtoupper(Str::random(6));
                } while (static::where('kode_booking', $kode)->exists());
                $r->kode_booking = $kode;
            }
        });

        // Jumlah malam selalu konsisten dengan tanggal (juga saat perpanjang / percepat menginap)
        static::saving(function ($r) {
            $r->jumlah_malam = max(1, static::hitungMalam($r->tanggal_masuk, $r->tanggal_keluar));
        });
    }

    public static function hitungMalam($masuk, $keluar): int
    {
        return (int) abs(Carbon::parse($masuk)->startOfDay()->diffInDays(Carbon::parse($keluar)->startOfDay()));
    }

    // ── Relasi ────────────────────────────────────────────────────────────────
    public function tamu()       { return $this->belongsTo(Tamu::class); }
    public function tipeKamar()  { return $this->belongsTo(TipeKamar::class); }
    public function kamar()      { return $this->belongsTo(Kamar::class); }
    public function dibuatOleh() { return $this->belongsTo(User::class, 'dibuat_oleh'); }
    public function folio()      { return $this->hasOne(Folio::class); }

    // ── Scope ─────────────────────────────────────────────────────────────────
    /** Reservasi yang masih memakai stok kamar. */
    public function scopeAktif($q)
    {
        return $q->whereIn('status', ['dikonfirmasi', 'check_in']);
    }

    /** Menimpa rentang malam [masuk, keluar): r.masuk < keluar DAN r.keluar > masuk. */
    public function scopeOverlap($q, $masuk, $keluar)
    {
        return $q->whereDate('tanggal_masuk', '<', Carbon::parse($keluar)->toDateString())
                 ->whereDate('tanggal_keluar', '>', Carbon::parse($masuk)->toDateString());
    }

    public function scopeKedatanganHariIni($q)
    {
        return $q->where('status', 'dikonfirmasi')->whereDate('tanggal_masuk', today());
    }

    public function scopeKeberangkatanHariIni($q)
    {
        return $q->where('status', 'check_in')->whereDate('tanggal_keluar', today());
    }

    public function scopeInHouse($q)
    {
        return $q->where('status', 'check_in');
    }

    // ── Status & aturan bisnis ────────────────────────────────────────────────
    public function bisaCheckIn(): bool
    {
        return $this->status === 'dikonfirmasi'
            && today()->gte($this->tanggal_masuk)
            && today()->lt($this->tanggal_keluar);
    }

    public function bisaCheckOut(): bool
    {
        return $this->status === 'check_in';
    }

    public function bisaDibatalkan(): bool
    {
        return $this->status === 'dikonfirmasi';
    }

    public function isTerlambat(): bool
    {
        return $this->status === 'check_in' && today()->gt($this->tanggal_keluar);
    }

    /** Tanggal keluar sebenarnya bila check-out dilakukan hari ini: minimal 1 malam, kelebihan inap tetap dihitung. */
    public function tanggalKeluarAktual(): Carbon
    {
        $masuk = Carbon::parse($this->tanggal_masuk)->startOfDay();
        $hari  = today();
        return $hari->lte($masuk) ? $masuk->copy()->addDay() : $hari->copy();
    }

    /** Hitung ulang jumlah malam dan total tarif kamar dari tarif yang berlaku. */
    public function hitungTotalKamar(): float
    {
        return $this->tipeKamar->totalTarif($this->tanggal_masuk, $this->tanggal_keluar);
    }

    public function getTotalKamarFormatAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_kamar, 0, ',', '.');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'dikonfirmasi' => ['label'=>'Dikonfirmasi', 'class'=>'bg-blue-100 text-blue-700'],
            'check_in'     => ['label'=>'In-House',     'class'=>'bg-green-100 text-green-700'],
            'check_out'    => ['label'=>'Check-Out',    'class'=>'bg-gray-100 text-gray-600'],
            'dibatalkan'   => ['label'=>'Dibatalkan',   'class'=>'bg-red-100 text-red-700'],
            'no_show'      => ['label'=>'No-Show',      'class'=>'bg-orange-100 text-orange-700'],
            default        => ['label'=>ucfirst($this->status), 'class'=>'bg-gray-100 text-gray-500'],
        };
    }

    public function getSumberLabelAttribute(): string
    {
        return match($this->sumber) {
            'walk_in' => 'Walk-in',
            'telepon' => 'Telepon',
            'website' => 'Website',
            'ota'     => 'OTA (Traveloka/Booking)',
            default   => ucfirst($this->sumber),
        };
    }
}
