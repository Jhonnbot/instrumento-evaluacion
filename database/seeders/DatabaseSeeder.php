<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // En producción solo cargamos los roles; nunca sembramos usuarios con claves conocidas.
        $this->call(RolSeeder::class);
    }
}
