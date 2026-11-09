<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Folio extends Model
{
    protected $table    = 'folio';
    protected $fillable = ['reservasi_id','nomor_folio','status','subtotal','diskon','service','pajak','total','terbayar','ditutup_at'];
    protected $casts    = [
        'subtotal' => 'decimal:2', 'diskon' => 'decimal:2', 'service' => 'decimal:2',
        'pajak' => 'decimal:2', 'total' => 'decimal:2', 'terbayar' => 'decimal:2',
        'ditutup_at' => 'datetime',
    ];

    public function reservasi() { return $this->belongsTo(Reservasi::class); }
    public function items()     { return $this->hasMany(FolioItem::class)->orderBy('tanggal')->orderBy('id'); }
    public function pembayaran(){ return $this->hasMany(Pembayaran::class)->orderBy('created_at'); }

    /** Buat folio untuk reservasi (satu reservasi = satu folio). */
    public static function untuk(Reservasi $reservasi): self
    {
        $folio = static::where('reservasi_id', $reservasi->id)->first();
        if ($folio) {
            return $folio;
        }

        do {
            $nomor = 'FOL-' . strtoupper(Str::random(8));
        } while (static::where('nomor_folio', $nomor)->exists());

        return static::create(['reservasi_id' => $reservasi->id, 'nomor_folio' => $nomor]);
    }

    /**
     * Hitung ulang seluruh total dari item dan pembayaran.
     * dasar = item (di luar diskon) - diskon ; service = dasar x %service ; pajak = (dasar + service) x %pajak
     */
    public function hitungUlang(): void
    {
        $items    = $this->items()->get();
        $subtotal = (float) $items->where('tipe', '!=', 'diskon')->sum('jumlah');
        $diskon   = abs((float) $items->where('tipe', 'diskon')->sum('jumlah'));
        $k        = static::hitungKomponen($subtotal, $diskon);

        $masuk  = (float) $this->pembayaran()->whereIn('jenis', ['deposit', 'pembayaran'])->sum('jumlah');
        $keluar = (float) $this->pembayaran()->where('jenis', 'refund')->sum('jumlah');

        $this->forceFill([
            'subtotal' => $subtotal,
            'diskon'   => $diskon,
            'service'  => $k['service'],
            'pajak'    => $k['pajak'],
            'total'    => $k['total'],
            'terbayar' => $masuk - $keluar,
        ])->save();
    }

    /** Rumus tagihan: service dari dasar, pajak dari (dasar + service). */
    public static function hitungKomponen(float $subtotal, float $diskon): array
    {
        $dasar   = max(0, $subtotal - $diskon);
        $service = round($dasar * (float) config('hotel.service_persen', 10) / 100);
        $pajak   = round(($dasar + $service) * (float) config('hotel.pajak_persen', 10) / 100);

        return ['dasar' => $dasar, 'service' => $service, 'pajak' => $pajak, 'total' => $dasar + $service + $pajak];
    }

    /** Proyeksi tagihan bila tamu check-out pada $keluarAktual (termasuk malam yang belum diposting). */
    public function proyeksiCheckout(Reservasi $reservasi, $keluarAktual): array
    {
        $keluarAktual = Carbon::parse($keluarAktual)->startOfDay();
        $sudah = $this->items()->where('tipe', 'kamar')->get()
            ->map(fn($i) => Carbon::parse($i->tanggal)->toDateString())->all();

        $belum = [];
        $tambahan = 0.0;
        $tgl = Carbon::parse($reservasi->tanggal_masuk)->startOfDay();
        while ($tgl->lt($keluarAktual)) {
            if (!in_array($tgl->toDateString(), $sudah, true)) {
                $harga = $reservasi->tipeKamar->hargaPerMalam($tgl);
                $belum[] = ['tanggal' => $tgl->copy(), 'harga' => $harga];
                $tambahan += $harga;
            }
            $tgl->addDay();
        }

        $k = static::hitungKomponen((float) $this->subtotal + $tambahan, (float) $this->diskon);

        return ['belum' => $belum, 'tambahan' => $tambahan, 'komponen' => $k, 'total' => $k['total'], 'sisa' => $k['total'] - (float) $this->terbayar];
    }

    /** Posting biaya kamar satu malam. Mengembalikan false jika malam itu sudah pernah diposting. */
    public function postingKamar(Reservasi $reservasi, $tanggal, ?int $userId = null): bool
    {
        $tanggal = Carbon::parse($tanggal)->startOfDay();

        $sudah = $this->items()->where('tipe', 'kamar')->whereDate('tanggal', $tanggal->toDateString())->exists();
        if ($sudah) {
            return false;
        }

        $tipe  = $reservasi->tipeKamar;
        $harga = $tipe->hargaPerMalam($tanggal);
        $nomor = $reservasi->kamar ? $reservasi->kamar->nomor : '-';

        $this->items()->create([
            'tanggal'      => $tanggal->toDateString(),
            'tipe'         => 'kamar',
            'deskripsi'    => 'Kamar ' . $nomor . ' (' . $tipe->nama . ') - ' . $tanggal->format('d M Y'),
            'qty'          => 1,
            'harga_satuan' => $harga,
            'jumlah'       => $harga,
            'dibuat_oleh'  => $userId,
        ]);

        $this->refresh();
        return true;
    }

    public function getSisaAttribute(): float
    {
        return (float) $this->total - (float) $this->terbayar;
    }

    public function getStatusBadgeAttribute(): array
    {
        return $this->status === 'tertutup'
            ? ['label'=>'Tertutup', 'class'=>'bg-gray-100 text-gray-600']
            : ['label'=>'Terbuka',  'class'=>'bg-green-100 text-green-700'];
    }
}
