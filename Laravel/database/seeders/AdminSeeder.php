<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@admin.com'], // Cek berdasarkan email ini
            [
                'name' => 'Admin Production',
                'password' => Hash::make('password'), // Ganti password sesuai keinginan
                'email_verified_at' => now(),
            ]
        );
    }
}
