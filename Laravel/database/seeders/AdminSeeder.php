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
                'name' => 'Admin Staff',
                'password' => Hash::make('password'), // Ganti password sesuai keinginan
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Daftar Role yang ingin dibuatkan user-nya
        $roles = ['sales', 'purchase', 'inventory', 'accounting', 'production'];

        foreach ($roles as $role) {
            // Membuat 1 user spesifik per role agar mudah login
            // Contoh: sales@admin.com, purchase@admin.com
            User::updateOrCreate([
                'email' => "{$role}@admin.com"
            ], [
                'name' => ucfirst($role) . " Staff", // Jadi: Sales Staff
                'password' => Hash::make('password'), // Password sama semua biar gampang
                'role' => $role,
                'email_verified_at' => now(),
            ]);
        }
    }
}
