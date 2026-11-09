<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusKamarLog extends Model
{
    protected $table    = 'status_kamar_log';
    protected $fillable = ['kamar_id','user_id','dari','ke','catatan'];

    public function kamar() { return $this->belongsTo(Kamar::class); }
    public function user()  { return $this->belongsTo(User::class); }
}
