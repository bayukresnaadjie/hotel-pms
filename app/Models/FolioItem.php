<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioItem extends Model
{
    protected $table    = 'folio_item';
    protected $fillable = ['folio_id','tanggal','tipe','layanan_id','deskripsi','qty','harga_satuan','jumlah','dibuat_oleh'];
    protected $casts    = ['tanggal' => 'date', 'harga_satuan' => 'decimal:2', 'jumlah' => 'decimal:2'];

    protected static function boot()
    {
        parent::boot();

        // Total folio selalu konsisten setelah item berubah
        static::saved(fn($item) => Folio::find($item->folio_id)?->hitungUlang());
        static::deleted(fn($item) => Folio::find($item->folio_id)?->hitungUlang());
    }

    public function folio()   { return $this->belongsTo(Folio::class); }
    public function layanan() { return $this->belongsTo(Layanan::class); }

    public function getTipeIconAttribute(): string
    {
        return match($this->tipe) {
            'kamar'   => '🛏️',
            'layanan' => '🛎️',
            'diskon'  => '🏷️',
            default   => '📄',
        };
    }
}
