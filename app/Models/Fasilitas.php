<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    protected $table    = 'fasilitas';
    protected $fillable = ['nama','icon'];

    public function tipeKamar() { return $this->belongsToMany(TipeKamar::class, 'fasilitas_tipe_kamar'); }
}
