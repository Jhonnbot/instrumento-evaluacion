<?php

use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Tema;
use App\Models\User;
use App\Services\Simulador\PracticaService;
use Database\Seeders\RolSeeder;

beforeEach(function (): void {
    $this->seed(RolSeeder::class);
});

test('incisos se muestran a b c d aunque se hayan registrado fuera de orden', function () {
    $categoria = Categoria::create(['nombre' => 'Incisos', 'estado' => true]);
    $tema = Tema::create(['id_categoria' => $categoria->getKey(), 'nombre' => 'Computación']);
    $pregunta = Pregunta::create(['id_tema' => $tema->getKey(), 'enunciado' => 'Una pregunta',
        'tipo' => 'opcion_multiple', 'estado' => true]);
    $pregunta->opciones()->createMany([
        ['texto_opcion' => 'b) CPU', 'es_correcta' => true],
        ['texto_opcion' => 'c) Disco', 'es_correcta' => false],
        ['texto_opcion' => 'a) RAM', 'es_correcta' => false],
        ['texto_opcion' => 'd) Fuente', 'es_correcta' => false],
    ]);
    config()->set('simulador.cantidad', 2);
    $otra = Pregunta::create(['id_tema' => $tema->getKey(), 'enunciado' => 'Otra pregunta',
        'tipo' => 'verdadero_falso', 'estado' => true]);
    $otra->opciones()->createMany([
        ['texto_opcion' => 'Verdadero', 'es_correcta' => true],
        ['texto_opcion' => 'Falso', 'es_correcta' => false],
    ]);
    $alumno = User::factory()->create();
    $practica = app(PracticaService::class)->iniciar($alumno);
    $bloque = app(PracticaService::class)->bloque($practica, 1);
    $opciones = collect($bloque)->firstWhere('id', (int) $pregunta->getKey())['opciones'];
    expect(array_column($opciones, 'texto'))->toBe(['a) RAM', 'b) CPU', 'c) Disco', 'd) Fuente']);
    $practica['respuestas'][(string) $pregunta->getKey()] = (int) $pregunta->opciones()->where('es_correcta', true)->first()->getKey();
    expect(app(PracticaService::class)->calificar($practica)['aciertos'])->toBe(1);
});
