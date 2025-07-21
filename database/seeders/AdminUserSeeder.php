<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@colegiopceirafaelgaleth.com'],
            [
                'name' => 'Administrador',
                'ap_paterno' => 'Sistema',
                'ap_materno' => 'Principal',
                'cedula' => '000000001',
                'celular' => '0999999999',
                'grado' => 'admin',
                'periodo' => 'N/A',
                'password' => Hash::make('R@faelgaleth/*'), // nueva contraseña
                'rol' => 'admin',  // Aseguramos que el rol sea 'admin'
                'id_user_moodle' => 0
            ]
        );
    }
}
