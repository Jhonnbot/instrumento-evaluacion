<?php

use App\Models\Categoria;
use App\Models\Examen;
use App\Models\Intento;
use App\Models\Tema;
use App\Models\User;
use App\Support\FechaMexico;
use Database\Seeders\ExamenSeeder;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed([RolSeeder::class, ExamenSeeder::class]);
});

/** Crea un resultado oficial sin depender del banco completo. */
function hu09Resultado(User $alumno, int $numero, int $puntos, string $utc = '2026-11-19 00:41:00'): Intento
{
    $intento = Intento::create([
        'id_usuario' => $alumno->getKey(), 'id_examen' => Examen::firstOrFail()->getKey(),
        'numero_intento' => $numero, 'fecha_inicio' => $utc,
        'fecha_fin' => $utc, 'estado' => 'enviado',
    ]);
    DB::table('resultados')->insert([
        'id_intento' => $intento->getKey(), 'calificacion_final' => $puntos,
        'dictamen' => $puntos >= 700 ? 'APROBADO' : 'REPROBADO',
        'fecha_calificacion' => $utc,
    ]);
    return $intento;
}

test('historiales exigen autenticación y rol correcto', function () {
    $this->get(route('alumno.historial.index'))->assertRedirect(route('login'));
    $this->get(route('coordinador.historial.index'))->assertRedirect(route('login'));
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    $this->actingAs($alumno)->get(route('coordinador.historial.index'))->assertForbidden();
    $this->actingAs($coordinador)->get(route('alumno.historial.index'))->assertForbidden();
    $this->actingAs(User::factory()->inactivo()->create())
        ->get(route('alumno.historial.index'))->assertRedirect(route('login'));
});

test('el alumno solo ve sus resultados oficiales y no intentos sin resultado', function () {
    $alumno = User::factory()->create();
    $otro = User::factory()->create();
    hu09Resultado($alumno, 1, 500);
    hu09Resultado($otro, 1, 900);
    Intento::create([
        'id_usuario' => $alumno->getKey(), 'id_examen' => Examen::firstOrFail()->getKey(),
        'numero_intento' => 2, 'fecha_inicio' => now(), 'estado' => 'en_curso',
    ]);
    $this->actingAs($alumno)->get(route('alumno.historial.index'))
        ->assertOk()->assertSee('1 evaluación(es) encontrada(s).')
        ->assertSee('500 / 1000')->assertDontSee('900 / 1000');
});

test('nadie puede consultar el detalle de un intento ajeno', function () {
    $alumno = User::factory()->create();
    $otro = User::factory()->create();
    $intento = hu09Resultado($otro, 1, 800);
    $this->actingAs($alumno)
        ->get(route('alumno.historial.detalle', $intento->getKey()))->assertNotFound();
    $this->actingAs(User::factory()->coordinador()->create())
        ->get(route('coordinador.historial.detalle', $intento->getKey()))->assertOk();
});

test('el coordinador filtra por nombre correo ID y dictamen', function () {
    $a = User::factory()->create(['nombre_completo' => 'Ana Pruebas', 'correo' => 'ana@example.com']);
    $b = User::factory()->create(['nombre_completo' => 'Luis Pruebas', 'correo' => 'luis@example.com']);
    hu09Resultado($a, 1, 800);
    hu09Resultado($b, 1, 500);
    $this->actingAs(User::factory()->coordinador()->create())
        ->get(route('coordinador.historial.index', ['buscar' => 'Ana', 'dictamen' => 'APROBADO']))
        ->assertOk()->assertSee('Ana Pruebas')->assertDontSee('Luis Pruebas');
    $this->get(route('coordinador.historial.index', ['buscar' => $b->getKey()]))
        ->assertOk()->assertSee('Luis Pruebas')->assertDontSee('Ana Pruebas');
    $this->get(route('coordinador.historial.index', ['buscar' => 'ana@example.com']))
        ->assertOk()->assertSee('Ana Pruebas')->assertDontSee('Luis Pruebas');
});

test('detalles muestran desglose por categoría y tema ya guardado', function () {
    $alumno = User::factory()->create();
    $intento = hu09Resultado($alumno, 1, 800);
    $categoria = Categoria::create(['nombre' => 'Ciberseguridad', 'estado' => true]);
    $tema = Tema::create(['id_categoria' => $categoria->getKey(), 'nombre' => 'Contraseñas seguras']);
    $idResultado = $intento->resultado->getKey();
    DB::table('detalle_resultado_categoria')->insert([
        'id_resultado' => $idResultado, 'id_categoria' => $categoria->getKey(),
        'puntaje_obtenido' => 200, 'ponderacion_aplicada' => 0.2,
    ]);
    DB::table('detalle_resultado_tema')->insert([
        'id_resultado' => $idResultado, 'id_tema' => $tema->getKey(),
        'preguntas_totales' => 2, 'respuestas_correctas' => 2,
    ]);
    $this->actingAs($alumno)->get(route('alumno.historial.detalle', $intento->getKey()))
        ->assertOk()->assertSee('Ciberseguridad')->assertSee('Contraseñas seguras')
        ->assertSee('2 de 2 pregunta(s)')->assertSee('800 / 1000');
});

test('las fechas UTC se presentan en horario de Ciudad de México sin alterar datos', function () {
    $alumno = User::factory()->create();
    $intento = hu09Resultado($alumno, 1, 500, '2026-11-19 00:41:00');
    $this->actingAs($alumno)->get(route('alumno.historial.index'))
        ->assertOk()->assertSee('18/11/2026 18:41');
    $this->get(route('alumno.historial.detalle', $intento->getKey()))
        ->assertOk()->assertSee('18/11/2026 18:41');
    expect($intento->fresh()->fecha_inicio->format('Y-m-d H:i:s'))->toBe('2026-11-19 00:41:00');
    expect(FechaMexico::mostrar('2026-11-19 00:41:00'))->toBe('18/11/2026 18:41');
});

test('los filtros de día usan el día de México y no el día UTC', function () {
    $alumno = User::factory()->create();
    hu09Resultado($alumno, 1, 500, '2026-11-19 00:41:00'); // 18 de noviembre, CDMX
    hu09Resultado($alumno, 2, 800, '2026-11-19 07:05:00'); // 19 de noviembre, CDMX
    $this->actingAs($alumno)
        ->get(route('alumno.historial.index', ['desde' => '2026-11-18', 'hasta' => '2026-11-18']))
        ->assertOk()->assertSee('500 / 1000')->assertDontSee('800 / 1000');
    $this->get(route('alumno.historial.index', ['hasta' => '2026-11-18']))
        ->assertOk()->assertSee('500 / 1000')->assertDontSee('800 / 1000');
    $this->get(route('alumno.historial.index', ['desde' => '2026-11-19']))
        ->assertOk()->assertSee('800 / 1000')->assertDontSee('500 / 1000');
});

test('filtros inválidos son rechazados', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('alumno.historial.index', ['dictamen' => 'INVENTADO']))
        ->assertSessionHasErrors('dictamen');
    $this->get(route('alumno.historial.index', ['desde' => '2026-11-20', 'hasta' => '2026-11-18']))
        ->assertSessionHasErrors('hasta');
});
