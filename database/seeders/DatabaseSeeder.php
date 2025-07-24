<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Comentar o eliminar otras líneas que llamen a otros seeders
        // $this->call(OtherSeeder::class);
        
        // Llamar al AdminUserSeeder
        $this->call(AdminUserSeeder::class);
    }
}
