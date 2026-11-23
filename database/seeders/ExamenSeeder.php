<?php

namespace Database\Seeders;

use App\Models\Examen;
use Illuminate\Database\Seeder;

class ExamenSeeder extends Seeder
{
    public function run(): void
    {
        Examen::query()->firstOrCreate(
            ['nombre' => 'Evaluación de conocimientos'],
            ['duracion_minutos' => 50, 'minimo_aprobatorio' => 700,
                'preguntas_por_pantalla' => 2, 'activo' => true],
        );
    }
}
