<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        Rol::query()->updateOrCreate(
            ['nombre' => 'Alumno'],
            ['descripcion' => 'Usuario que realiza simuladores y examenes'],
        );

        Rol::query()->updateOrCreate(
            ['nombre' => 'Coordinador'],
            ['descripcion' => 'Usuario que administra alumnos, preguntas y evaluaciones'],
        );
    }
}
