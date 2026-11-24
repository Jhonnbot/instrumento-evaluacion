<?php

use App\Models\Constancia;
use App\Models\Examen;
use App\Models\Intento;
use App\Models\User;
use Database\Seeders\ExamenSeeder;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed([RolSeeder::class, ExamenSeeder::class]);
    Storage::fake('local');
});

function hu10Resultado(User $alumno, int $numero = 1, int $puntos = 850): int
{
    $intento = Intento::query()->create([
        'id_usuario' => $alumno->getKey(),
        'id_examen' => Examen::firstOrFail()->getKey(),
        'numero_intento' => $numero,
        'fecha_inicio' => '2026-11-19 00:00:00',
        'fecha_fin' => '2026-11-19 00:41:00',
        'estado' => 'enviado',
    ]);

    return DB::table('resultados')->insertGetId([
        'id_intento' => $intento->getKey(),
        'calificacion_final' => $puntos,
        'dictamen' => $puntos >= 700 ? 'APROBADO' : 'REPROBADO',
        'fecha_calificacion' => '2026-11-19 00:41:00',
    ]);
}

test('solo alumno autenticado puede emitir y ver sus constancias', function () {
    $this->get(route('alumno.constancias.index'))->assertRedirect(route('login'));
    $coord = User::factory()->coordinador()->create();
    $this->actingAs($coord)->get(route('alumno.constancias.index'))->assertForbidden();
    $alumno = User::factory()->create();
    $this->actingAs($alumno)->get(route('coordinador.constancias.index'))->assertForbidden();
    $this->actingAs($coord)->get(route('coordinador.constancias.index'))->assertOk();
});

test('un examen reprobado nunca emite constancia', function () {
    $alumno = User::factory()->create();
    $id = hu10Resultado($alumno, 1, 650);
    $this->actingAs($alumno)->post(route('alumno.constancias.generar', $id))
        ->assertSessionHasErrors('constancia');
    expect(Constancia::count())->toBe(0);
});

test('el alumno no puede emitir ni descargar constancia de otro alumno', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $id = hu10Resultado($a);
    $this->actingAs($b)->post(route('alumno.constancias.generar', $id))->assertNotFound();
    $this->actingAs($a)->post(route('alumno.constancias.generar', $id))->assertRedirect();
    $constancia = Constancia::firstOrFail();
    $this->actingAs($b)->get(route('alumno.constancias.descargar', $constancia->getKey()))->assertNotFound();
});

test('emitir dos veces conserva un solo folio y un PDF privado', function () {
    $alumno = User::factory()->create();
    $id = hu10Resultado($alumno);
    $this->actingAs($alumno)->post(route('alumno.constancias.generar', $id))->assertRedirect();
    $constancia = Constancia::firstOrFail();
    Storage::disk('local')->assertExists($constancia->ruta_pdf);
    expect(str_starts_with(Storage::disk('local')->get($constancia->ruta_pdf), '%PDF'))->toBeTrue();
    $this->post(route('alumno.constancias.generar', $id))->assertRedirect();
    expect(Constancia::count())->toBe(1);
    expect(Constancia::firstOrFail()->folio)->toBe($constancia->folio);
    $this->get(route('alumno.constancias.descargar', $constancia->getKey()))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

test('verificación pública exige clave correcta y no revela CURP', function () {
    $alumno = User::factory()->create(['nombre_completo' => 'Lola Pruebas']);
    $id = hu10Resultado($alumno);
    $this->actingAs($alumno)->post(route('alumno.constancias.generar', $id));
    $constancia = Constancia::firstOrFail();
    auth()->logout();
    $this->get(route('constancias.verificar.qr', $constancia->clave_verificacion))
        ->assertOk()->assertSee('AUTÉNTICA Y VIGENTE')->assertSee('Lola Pruebas')
        ->assertDontSee('CURP')->assertDontSee($alumno->correo);
    $this->post(route('constancias.verificar.consultar'), [
        'folio' => $constancia->folio, 'clave' => $constancia->clave_verificacion,
    ])->assertOk()->assertSee('AUTÉNTICA Y VIGENTE');
    $this->post(route('constancias.verificar.consultar'), [
        'folio' => $constancia->folio, 'clave' => 'incorrecta',
    ])->assertOk()->assertSee('Constancia no encontrada');
});

test('revocar invalida verificación y bloquea descarga sin reemitir', function () {
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    $id = hu10Resultado($alumno);
    $this->actingAs($alumno)->post(route('alumno.constancias.generar', $id));
    $constancia = Constancia::firstOrFail();
    $this->actingAs($coordinador)->post(route('coordinador.constancias.revocar', $constancia->getKey()))->assertRedirect();
    $this->get(route('constancias.verificar.qr', $constancia->clave_verificacion))
        ->assertOk()->assertSee('CONSTANCIA REVOCADA');
    $this->actingAs($alumno)->get(route('alumno.constancias.descargar', $constancia->getKey()))->assertForbidden();
    $this->post(route('alumno.constancias.generar', $id))->assertRedirect();
    expect(Constancia::count())->toBe(1);
    expect($constancia->fresh()->estado)->toBe('revocada');
});

test('los no autenticados no pueden descargar documentos privados', function () {
    $this->get(route('alumno.constancias.descargar', 1))->assertRedirect(route('login'));
    $this->get(route('constancias.verificar.formulario'))->assertOk();
});
