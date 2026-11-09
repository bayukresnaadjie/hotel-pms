<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightAudit extends Model
{
    protected $table    = 'night_audit';
    protected $fillable = ['tanggal','dijalankan_oleh','jumlah_in_house','jumlah_diposting','total_pendapatan_kamar','okupansi','catatan'];
    protected $casts    = ['tanggal' => 'date', 'total_pendapatan_kamar' => 'decimal:2', 'okupansi' => 'decimal:2'];

    public function dijalankanOleh() { return $this->belongsTo(User::class, 'dijalankan_oleh'); }
}
