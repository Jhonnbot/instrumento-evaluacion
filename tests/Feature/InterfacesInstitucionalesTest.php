<?php

use App\Models\User;
use Database\Seeders\RolSeeder;

beforeEach(fn () => $this->seed(RolSeeder::class));

test('el acceso y el registro presentan los prototipos sin modificar la autenticacion', function () {
    $this->get(route('login'))->assertOk()->assertSee('Tu siguiente paso')->assertSee('Ingresar a mi cuenta');
    $this->get(route('register'))->assertOk()->assertSee('Tu camino al logro')->assertSee('Crear cuenta de alumno');
});

test('los paneles muestran navegacion real segun el rol', function () {
    $alumno = User::factory()->create();
    $coordinador = User::factory()->coordinador()->create();
    $this->actingAs($alumno)->get(route('alumno.panel'))->assertOk()->assertSee('Simulador de práctica')->assertDontSee('Gestión de alumnos');
    $this->actingAs($coordinador)->get(route('coordinador.panel'))->assertOk()->assertSee('Gestión de alumnos')->assertSee('Solicitudes pendientes');
    $this->actingAs($alumno)->get(route('coordinador.panel'))->assertForbidden();
});

test('los indicadores del panel administrativo usan los datos registrados y no los valores ficticios del prototipo', function () {
    User::factory()->count(2)->create();
    $coordinador = User::factory()->coordinador()->create();
    $this->actingAs($coordinador)->get(route('coordinador.panel'))
        ->assertOk()->assertSee('Alumnos registrados')->assertSee('>2</strong>', false)
        ->assertDontSee('>128</strong>', false);
});

test('el portal de verificacion permanece publico', function () {
    $this->get(route('constancias.verificar.formulario'))->assertOk()->assertSee('Consultar constancia');
});
