<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    protected $table    = 'layanan';
    protected $fillable = ['kode','nama','kategori','harga','aktif'];
    protected $casts    = ['harga' => 'decimal:2', 'aktif' => 'boolean'];

    public function getHargaFormatAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->harga, 0, ',', '.');
    }

    public function getKategoriLabelAttribute(): string
    {
        return match($this->kategori) {
            'fnb'       => '🍽️ Makanan & Minuman',
            'laundry'   => '👕 Laundry',
            'spa'       => '💆 Spa',
            'transport' => '🚗 Transportasi',
            default     => '🛎️ Lainnya',
        };
    }
}
