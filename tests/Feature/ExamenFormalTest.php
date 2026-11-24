<?php

use App\Models\Categoria;
use App\Models\Examen;
use App\Models\Intento;
use App\Models\Pregunta;
use App\Models\Tema;
use App\Models\User;
use App\Services\Evaluaciones\ExamenService;
use Database\Seeders\ExamenSeeder;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RolSeeder::class);
    $this->seed(ExamenSeeder::class);
    config()->set('examen_cocosoft.preguntas_por_categoria', 2);
});

function hu07Banco(): void
{
    foreach (range(1, 5) as $n) {
        $categoria = Categoria::create(['nombre' => 'Categoría examen '.$n, 'estado' => true]);
        $tema = Tema::create(['id_categoria' => $categoria->getKey(), 'nombre' => 'Tema '.$n]);
        for ($i = 1; $i <= 2; $i++) {
            $pregunta = Pregunta::create(['id_tema' => $tema->getKey(),
                'tipo' => 'opcion_multiple', 'estado' => true, 'enunciado' => "Pregunta examen {$n}-{$i}"]);
            // Se introduce b) antes de a) para comprobar la presentación ordenada sin cambiar IDs.
            $pregunta->opciones()->createMany([
                ['texto_opcion' => 'b) Incorrecta', 'es_correcta' => false],
                ['texto_opcion' => 'a) Correcta', 'es_correcta' => true],
            ]);
        }
    }
}

test('el examen solo admite alumnos autenticados activos', function () {
    $this->get(route('alumno.examen.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->coordinador()->create())
        ->get(route('alumno.examen.index'))->assertForbidden();
    $this->actingAs(User::factory()->inactivo()->create())
        ->get(route('alumno.examen.index'))->assertRedirect();
    $this->actingAs(User::factory()->create())->get(route('alumno.examen.index'))->assertOk();
});

test('no consume un intento cuando faltan categorias o reactivos', function () {
    $this->actingAs(User::factory()->create());
    $this->post(route('alumno.examen.iniciar'))->assertSessionHasErrors('banco');
    expect(Intento::count())->toBe(0);
    hu07Banco();
    $this->post(route('alumno.examen.iniciar'))->assertRedirect();
    expect(Intento::count())->toBe(1);
    expect(DB::table('preguntas_intento')->count())->toBe(10);
});

test('un intento dura 50 minutos, muestra dos preguntas e incisos consecutivos', function () {
    hu07Banco();
    $alumno = User::factory()->create();
    $this->actingAs($alumno)->post(route('alumno.examen.iniciar'));
    $intento = Intento::firstOrFail();
    $this->get(route('alumno.examen.preguntas', [$intento->getKey(), 1]))
        ->assertOk()->assertSee('Página 1 de 5')->assertSee('a) Correcta')
        ->assertSee('b) Incorrecta')->assertDontSee('es_correcta');
    $this->get(route('alumno.examen.preguntas', [$intento->getKey(), 6]))->assertNotFound();
    $this->post(route('alumno.examen.iniciar'));
    expect(Intento::count())->toBe(1);
});

test('guarda respuestas, califica mil puntos y bloquea otro intento al aprobar', function () {
    hu07Banco();
    $alumno = User::factory()->create();
    $servicio = app(ExamenService::class);
    $examen = Examen::firstOrFail();
    $intento = $servicio->iniciar($alumno, $examen);
    foreach (range(1, 5) as $pagina) {
        $bloque = $servicio->bloque($intento, $pagina);
        $respuestas = [];
        foreach ($bloque as $p) {
            $pregunta = Pregunta::with('opciones')->findOrFail($p['id']);
            $respuestas[$p['id']] = $pregunta->opciones->firstWhere('es_correcta', true)->getKey();
        }
        $servicio->guardar($alumno, $intento->getKey(), $pagina, $respuestas, $pagina === 5);
    }
    $resultado = DB::table('resultados')->first();
    expect($resultado->calificacion_final)->toBe(1000);
    expect($resultado->dictamen)->toBe('APROBADO');
    expect(DB::table('detalle_resultado_categoria')->count())->toBe(5);
    expect(DB::table('detalle_resultado_tema')->count())->toBe(5);
    expect(DB::table('respuestas_opciones')->count())->toBe(10);
    $this->actingAs($alumno)->get(route('alumno.examen.resultado', $intento->getKey()))
        ->assertOk()->assertSee('1000 / 1000');
    $this->post(route('alumno.examen.iniciar'))->assertSessionHasErrors('examen');
    expect(Intento::count())->toBe(1);
});

test('dos intentos reprobados bloquean el tercero si no hay autorización', function () {
    hu07Banco();
    $alumno = User::factory()->create();
    $servicio = app(ExamenService::class);
    $examen = Examen::firstOrFail();
    foreach (range(1, 2) as $numero) {
        $intento = $servicio->iniciar($alumno, $examen);
        expect($intento->numero_intento)->toBe($numero);
        $servicio->finalizar($intento->getKey()); // Se entrega sin respuestas.
    }
    expect(DB::table('resultados')->where('dictamen', 'REPROBADO')->count())->toBe(2);
    $this->actingAs($alumno)->post(route('alumno.examen.iniciar'))->assertSessionHasErrors('examen');
    expect(Intento::count())->toBe(2);
});

test('el vencimiento se comprueba en servidor y cerrar dos veces no duplica resultados', function () {
    hu07Banco();
    $alumno = User::factory()->create();
    $servicio = app(ExamenService::class);
    $intento = $servicio->iniciar($alumno, Examen::firstOrFail());
    $this->travel(51)->minutes();
    $servicio->verificarPropiedad($alumno, $intento->getKey());
    $servicio->finalizar($intento->getKey());
    expect(Intento::findOrFail($intento->getKey())->estado)->toBe('tiempo_agotado');
    expect(DB::table('resultados')->count())->toBe(1);
    expect(DB::table('resultados')->first()->calificacion_final)->toBe(0);
});

test('un alumno no puede consultar ni modificar el intento ajeno', function () {
    hu07Banco();
    $dueño = User::factory()->create();
    $otro = User::factory()->create();
    $intento = app(ExamenService::class)->iniciar($dueño, Examen::firstOrFail());
    $this->actingAs($otro)
        ->get(route('alumno.examen.preguntas', [$intento->getKey(), 1]))->assertNotFound();
    $this->post(route('alumno.examen.guardar', [$intento->getKey(), 1]),
        ['accion' => 'siguiente', 'respuestas' => []])->assertNotFound();
});

test('un tercer intento requiere aprobación y la autorización solo se consume una vez', function () {
    hu07Banco();
    $alumno = User::factory()->create();
    $servicio = app(ExamenService::class);
    $examen = Examen::firstOrFail();
    foreach (range(1, 2) as $numero) {
        $intento = $servicio->iniciar($alumno, $examen);
        $servicio->finalizar($intento->getKey());
    }
    $idSolicitud = DB::table('solicitudes_intento')->insertGetId([
        'id_usuario' => $alumno->getKey(), 'estado' => 'aprobada',
        'fecha_solicitud' => now(), 'fecha_resolucion' => now(),
    ]);
    $tercero = $servicio->iniciar($alumno, $examen);
    expect($tercero->numero_intento)->toBe(3);
    expect((int) $tercero->id_solicitud)->toBe((int) $idSolicitud);
    $servicio->finalizar($tercero->getKey());
    $this->actingAs($alumno)->post(route('alumno.examen.iniciar'))->assertSessionHasErrors('examen');
    expect(Intento::count())->toBe(3);
});
