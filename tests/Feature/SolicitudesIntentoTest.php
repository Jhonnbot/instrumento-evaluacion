<?php

use App\Models\Categoria;
use App\Models\Examen;
use App\Models\Intento;
use App\Models\Pregunta;
use App\Models\SolicitudIntento;
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

/** Prueba el flujo de HU-08 sin hacer un examen completo en cada prueba. */
function hu08ReprobarDos(User $alumno): void
{
    foreach ([1, 2] as $numero) {
        $intento = Intento::query()->create([
            'id_usuario' => $alumno->getKey(), 'id_examen' => Examen::firstOrFail()->getKey(),
            'numero_intento' => $numero, 'fecha_inicio' => now(),
            'fecha_fin' => now(), 'estado' => 'enviado',
        ]);
        DB::table('resultados')->insert([
            'id_intento' => $intento->getKey(), 'calificacion_final' => 500,
            'dictamen' => 'REPROBADO', 'fecha_calificacion' => now(),
        ]);
    }
}

function hu08BancoCompleto(): void
{
    foreach (range(1, 5) as $c) {
        $categoria = Categoria::create(['nombre' => 'HU08 Categoria '.$c, 'estado' => true]);
        $tema = Tema::create(['id_categoria' => $categoria->getKey(), 'nombre' => 'HU08 Tema '.$c]);
        foreach (range(1, 2) as $n) {
            $pregunta = Pregunta::create(['id_tema' => $tema->getKey(),
                'tipo' => 'opcion_multiple', 'enunciado' => "Pregunta {$c}-{$n}", 'estado' => true]);
            $pregunta->opciones()->createMany([
                ['texto_opcion' => 'a) Correcta', 'es_correcta' => true],
                ['texto_opcion' => 'b) Incorrecta', 'es_correcta' => false],
            ]);
        }
    }
}

test('solo alumnos activos pueden enviar y coordinadores pueden resolver solicitudes', function () {
    $this->get(route('alumno.solicitudes.index'))->assertRedirect(route('login'));
    $this->get(route('coordinador.solicitudes.index'))->assertRedirect(route('login'));
    $coord = User::factory()->coordinador()->create();
    $this->actingAs($coord)->get(route('alumno.solicitudes.index'))->assertForbidden();
    $this->actingAs($coord)->post(route('alumno.solicitudes.store'))->assertForbidden();
    $alumno = User::factory()->create();
    $this->actingAs($alumno)->get(route('coordinador.solicitudes.index'))->assertForbidden();
    $this->actingAs($alumno)->post(route('coordinador.solicitudes.resolver', 999),
        ['decision' => 'aprobar'])->assertForbidden();
    $this->actingAs(User::factory()->inactivo()->create())->get(route('alumno.solicitudes.index'))->assertRedirect(route('login'));
});

test('alumno sin dos intentos reprobados no puede solicitar otro', function () {
    $alumno = User::factory()->create();
    $this->actingAs($alumno)->post(route('alumno.solicitudes.store'))
        ->assertSessionHasErrors('solicitud');
    expect(SolicitudIntento::count())->toBe(0);
    $this->get(route('alumno.solicitudes.index'))->assertOk()->assertDontSee('Solicitud enviada.');
});

test('tras dos reprobados puede enviar una sola solicitud pendiente', function () {
    $alumno = User::factory()->create();
    hu08ReprobarDos($alumno);
    $this->actingAs($alumno)->post(route('alumno.solicitudes.store'))
        ->assertRedirect(route('alumno.solicitudes.index'));
    expect(SolicitudIntento::count())->toBe(1);
    expect(SolicitudIntento::firstOrFail()->estado)->toBe('pendiente');
    $this->post(route('alumno.solicitudes.store'))->assertSessionHasErrors('solicitud');
    expect(SolicitudIntento::count())->toBe(1);
});

test('coordinador aprueba y solo se habilita un tercer intento enlazado', function () {
    hu08BancoCompleto();
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    hu08ReprobarDos($alumno);
    $this->actingAs($alumno)->post(route('alumno.solicitudes.store'));
    $solicitud = SolicitudIntento::firstOrFail();
    $this->actingAs($coordinador)->post(route('coordinador.solicitudes.resolver', $solicitud->getKey()), [
        'decision' => 'aprobar', 'observaciones' => 'Puede presentar una vez más.',
    ])->assertRedirect(route('coordinador.solicitudes.index'));
    $solicitud->refresh();
    expect($solicitud->estado)->toBe('aprobada');
    expect($solicitud->id_coordinador)->toBe($coordinador->getKey());
    expect($solicitud->fecha_resolucion)->not->toBeNull();
    $this->actingAs($alumno)->get(route('alumno.solicitudes.index'))
        ->assertOk()->assertSee('Autorización disponible');
    $tercero = app(ExamenService::class)->iniciar($alumno, Examen::firstOrFail());
    expect($tercero->numero_intento)->toBe(3);
    expect((int) $tercero->id_solicitud)->toBe($solicitud->getKey());
    app(ExamenService::class)->finalizar($tercero->getKey());
    expect(Intento::count())->toBe(3);
    $this->actingAs($alumno)->post(route('alumno.examen.iniciar'))
        ->assertSessionHasErrors('examen');
    // Tras reprobar el intento autorizado, una nueva solicitud será posible, nunca automática.
    $this->post(route('alumno.solicitudes.store'))->assertRedirect(route('alumno.solicitudes.index'));
    expect(SolicitudIntento::count())->toBe(2);
});

test('rechazar solicitud no habilita intento y permite volver a solicitar', function () {
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    hu08ReprobarDos($alumno);
    $this->actingAs($alumno)->post(route('alumno.solicitudes.store'));
    $solicitud = SolicitudIntento::firstOrFail();
    $this->actingAs($coordinador)->post(route('coordinador.solicitudes.resolver', $solicitud->getKey()), [
        'decision' => 'rechazar', 'observaciones' => 'Revisar preparación.',
    ])->assertRedirect(route('coordinador.solicitudes.index'));
    expect($solicitud->fresh()->estado)->toBe('rechazada');
    expect(app(ExamenService::class)->estado($alumno, Examen::firstOrFail())['puede_iniciar'])->toBeFalse();
    $this->actingAs($alumno)->get(route('alumno.solicitudes.index'))->assertSee('Revisar preparación.');
    $this->post(route('alumno.solicitudes.store'))->assertRedirect(route('alumno.solicitudes.index'));
    expect(SolicitudIntento::count())->toBe(2);
});

test('doble resolución no altera una solicitud ya resuelta', function () {
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    hu08ReprobarDos($alumno);
    $this->actingAs($alumno)->post(route('alumno.solicitudes.store'));
    $id = SolicitudIntento::firstOrFail()->getKey();
    $this->actingAs($coordinador)->post(route('coordinador.solicitudes.resolver', $id),
        ['decision' => 'aprobar'])->assertRedirect();
    $this->post(route('coordinador.solicitudes.resolver', $id),
        ['decision' => 'rechazar'])->assertSessionHasErrors('solicitud');
    expect(SolicitudIntento::findOrFail($id)->estado)->toBe('aprobada');
});

test('no permite segunda aprobación si ya tiene otra sin utilizar', function () {
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    hu08ReprobarDos($alumno);
    SolicitudIntento::create(['id_usuario' => $alumno->getKey(), 'estado' => 'aprobada', 'fecha_solicitud' => now()]);
    $otra = SolicitudIntento::create(['id_usuario' => $alumno->getKey(), 'estado' => 'pendiente', 'fecha_solicitud' => now()]);
    $this->actingAs($coordinador)->post(route('coordinador.solicitudes.resolver', $otra->getKey()),
        ['decision' => 'aprobar'])->assertSessionHasErrors('solicitud');
    expect($otra->fresh()->estado)->toBe('pendiente');
});

test('un alumno solo puede consultar sus propias solicitudes', function () {
    $alumno = User::factory()->create();
    $otro = User::factory()->create();
    hu08ReprobarDos($alumno);
    $this->actingAs($alumno)->post(route('alumno.solicitudes.store'));
    $this->actingAs($otro)->get(route('alumno.solicitudes.index'))
        ->assertOk()->assertDontSee('Solicitud #1');
});
