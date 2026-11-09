<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HousekeepingTask extends Model
{
    protected $table    = 'housekeeping_task';
    protected $fillable = ['kamar_id','ditugaskan_ke','tipe','status','tanggal','mulai_at','selesai_at','catatan'];
    protected $casts    = ['tanggal' => 'date', 'mulai_at' => 'datetime', 'selesai_at' => 'datetime'];

    public function kamar()   { return $this->belongsTo(Kamar::class); }
    public function petugas() { return $this->belongsTo(User::class, 'ditugaskan_ke'); }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'menunggu'   => ['label'=>'Menunggu',   'class'=>'bg-gray-100 text-gray-600'],
            'dikerjakan' => ['label'=>'Dikerjakan', 'class'=>'bg-yellow-100 text-yellow-700'],
            'selesai'    => ['label'=>'Selesai',    'class'=>'bg-green-100 text-green-700'],
            default      => ['label'=>ucfirst($this->status), 'class'=>'bg-gray-100 text-gray-500'],
        };
    }

    public function getTipeLabelAttribute(): string
    {
        return match($this->tipe) {
            'checkout_clean' => 'Pembersihan Check-out',
            'stayover'       => 'Stayover',
            'inspeksi'       => 'Inspeksi',
            default          => ucfirst($this->tipe),
        };
    }
}
