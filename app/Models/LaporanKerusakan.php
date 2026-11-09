<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanKerusakan extends Model
{
    protected $table    = 'laporan_kerusakan';
    protected $fillable = ['kamar_id','dilaporkan_oleh','judul','deskripsi','prioritas','status','kamar_tidak_bisa_dipakai','diselesaikan_at'];
    protected $casts    = ['kamar_tidak_bisa_dipakai' => 'boolean', 'diselesaikan_at' => 'datetime'];

    public function kamar()      { return $this->belongsTo(Kamar::class); }
    public function pelapor()    { return $this->belongsTo(User::class, 'dilaporkan_oleh'); }

    public function getPrioritasBadgeAttribute(): array
    {
        return match($this->prioritas) {
            'tinggi' => ['label'=>'Tinggi', 'class'=>'bg-red-100 text-red-700'],
            'sedang' => ['label'=>'Sedang', 'class'=>'bg-yellow-100 text-yellow-700'],
            default  => ['label'=>'Rendah', 'class'=>'bg-green-100 text-green-700'],
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'dilaporkan' => ['label'=>'Dilaporkan', 'class'=>'bg-red-100 text-red-700'],
            'diproses'   => ['label'=>'Diproses',   'class'=>'bg-yellow-100 text-yellow-700'],
            'selesai'    => ['label'=>'Selesai',    'class'=>'bg-green-100 text-green-700'],
            default      => ['label'=>ucfirst($this->status), 'class'=>'bg-gray-100 text-gray-500'],
        };
    }
}
