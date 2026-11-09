<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name','email','password','role','aktif','last_login'];
    protected $hidden   = ['password','remember_token'];
    protected $casts    = [
        'email_verified_at' => 'datetime',
        'last_login'        => 'datetime',
        'aktif'             => 'boolean',
    ];

    public function punyaRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getRoleBadgeAttribute(): array
    {
        return match($this->role) {
            'admin'        => ['label'=>'Admin',        'class'=>'bg-red-100 text-red-700'],
            'frontdesk'    => ['label'=>'Front Desk',   'class'=>'bg-blue-100 text-blue-700'],
            'housekeeping' => ['label'=>'Housekeeping', 'class'=>'bg-green-100 text-green-700'],
            'finance'      => ['label'=>'Finance',      'class'=>'bg-purple-100 text-purple-700'],
            default        => ['label'=>ucfirst($this->role), 'class'=>'bg-gray-100 text-gray-600'],
        };
    }

    public function getAvatarUrlAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=B45309&color=fff&size=64';
    }
}
