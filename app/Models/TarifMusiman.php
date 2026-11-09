<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TarifMusiman extends Model
{
    protected $table    = 'tarif_musiman';
    protected $fillable = ['nama','tanggal_mulai','tanggal_selesai','pengali','tipe_kamar_id','aktif'];
    protected $casts    = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'pengali'         => 'decimal:2',
        'aktif'           => 'boolean',
    ];

    public function tipeKamar() { return $this->belongsTo(TipeKamar::class); }

    public function scopeBerlakuPada($query, $tanggal, ?int $tipeKamarId = null)
    {
        $tanggal = Carbon::parse($tanggal)->toDateString();

        return $query->where('aktif', true)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->where(function ($w) use ($tipeKamarId) {
                $w->whereNull('tipe_kamar_id');
                if ($tipeKamarId) {
                    $w->orWhere('tipe_kamar_id', $tipeKamarId);
                }
            });
    }
}
