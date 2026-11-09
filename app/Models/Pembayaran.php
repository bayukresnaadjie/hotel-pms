<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table    = 'pembayaran';
    protected $fillable = ['folio_id','nomor_pembayaran','jenis','metode','jumlah','referensi','diterima_oleh'];
    protected $casts    = ['jumlah' => 'decimal:2'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($p) {
            if (empty($p->nomor_pembayaran)) {
                do {
                    $nomor = 'PAY' . now()->format('ymdHis') . rand(100, 999);
                } while (static::where('nomor_pembayaran', $nomor)->exists());
                $p->nomor_pembayaran = $nomor;
            }
        });

        static::saved(fn($p) => Folio::find($p->folio_id)?->hitungUlang());
        static::deleted(fn($p) => Folio::find($p->folio_id)?->hitungUlang());
    }

    public function folio()       { return $this->belongsTo(Folio::class); }
    public function diterimaOleh(){ return $this->belongsTo(User::class, 'diterima_oleh'); }

    public function getMetodeLabelAttribute(): string
    {
        return match($this->metode) {
            'tunai'        => 'Tunai',
            'kartu_debit'  => 'Kartu Debit',
            'kartu_kredit' => 'Kartu Kredit',
            'transfer'     => 'Transfer Bank',
            'qris'         => 'QRIS',
            default        => ucfirst($this->metode),
        };
    }

    public function getJenisBadgeAttribute(): array
    {
        return match($this->jenis) {
            'deposit'    => ['label'=>'Deposit',    'class'=>'bg-blue-100 text-blue-700'],
            'pembayaran' => ['label'=>'Pembayaran', 'class'=>'bg-green-100 text-green-700'],
            'refund'     => ['label'=>'Refund',     'class'=>'bg-red-100 text-red-700'],
            default      => ['label'=>ucfirst($this->jenis), 'class'=>'bg-gray-100 text-gray-500'],
        };
    }
}
