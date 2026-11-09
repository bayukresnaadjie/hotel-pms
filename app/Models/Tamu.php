<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tamu extends Model
{
    use SoftDeletes;

    protected $table    = 'tamu';
    protected $fillable = [
        'nama','email','telepon','jenis_identitas','nomor_identitas',
        'alamat','kota','negara','tanggal_lahir','tipe','catatan',
    ];
    protected $casts = ['tanggal_lahir' => 'date'];

    public function reservasi() { return $this->hasMany(Reservasi::class); }

    public function getTotalMenginapAttribute(): int
    {
        return $this->reservasi()->where('status', 'check_out')->count();
    }

    public function getTotalMalamAttribute(): int
    {
        return (int) $this->reservasi()->where('status', 'check_out')->sum('jumlah_malam');
    }

    public function getTipeBadgeAttribute(): array
    {
        return match($this->tipe) {
            'vip'       => ['label'=>'VIP',       'class'=>'bg-amber-100 text-amber-700',  'icon'=>'⭐'],
            'blacklist' => ['label'=>'Blacklist', 'class'=>'bg-red-100 text-red-700',      'icon'=>'⛔'],
            default     => ['label'=>'Reguler',   'class'=>'bg-gray-100 text-gray-600',    'icon'=>''],
        };
    }

    public function getInisialAttribute(): string
    {
        return strtoupper(substr($this->nama, 0, 1));
    }
}
