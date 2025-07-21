<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario de prueba (Jahir)
            User::create([
            'name' => 'Jahir',
            'email' => 'jahir@gmail.com',
            'id_user_moodle' => 1001, // corregido
            'ap_paterno' => 'Gómez',
            'ap_materno' => 'Martínez',
            'celular' => '0999999999',
            'cedula' => '1100000000',
            'grado' => '1BGU',
            'periodo' => '2025',
            'password' => Hash::make('jahir123'),
        ]);

        // Usuario administrador (admin@gmail.com)
        $this->call(AdminUserSeeder::class);
    }
}
