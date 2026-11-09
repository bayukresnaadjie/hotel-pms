<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Admin Hotel',        'admin@hotel.test',     'admin'],
            ['Rina Front Desk',    'frontdesk@hotel.test', 'frontdesk'],
            ['Dewi Front Desk',    'dewi@hotel.test',      'frontdesk'],
            ['Sari Housekeeping',  'hk1@hotel.test',       'housekeeping'],
            ['Wawan Housekeeping', 'hk2@hotel.test',       'housekeeping'],
            ['Budi Finance',       'finance@hotel.test',   'finance'],
        ];

        $password = Hash::make('password'); // di-hash sekali, dipakai semua akun demo

        foreach ($users as [$name, $email, $role]) {
            User::create(['name' => $name, 'email' => $email, 'role' => $role, 'aktif' => true, 'password' => $password]);
        }

        $this->command->info('✅ ' . count($users) . ' user dibuat');
    }
}
