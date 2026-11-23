<?php

use App\Models\Rol;
use App\Models\User;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(fn () => $this->seed(RolSeeder::class));

test('se crean las dieciseis tablas funcionales y no la tabla users', function () {
    $tablas = [
        'roles', 'usuarios', 'categorias', 'temas', 'preguntas', 'opciones_respuesta',
        'examenes', 'solicitudes_intento', 'intentos', 'preguntas_intento',
        'respuestas_intento', 'respuestas_opciones', 'resultados',
        'detalle_resultado_categoria', 'detalle_resultado_tema', 'constancias',
    ];

    foreach ($tablas as $tabla) {
        expect(Schema::hasTable($tabla))->toBeTrue();
    }
    expect(Schema::hasTable('users'))->toBeFalse();
});

test('el registro crea exclusivamente un alumno y almacena una contraseña cifrada', function () {
    $respuesta = $this->post('/registro', [
        'nombre_completo' => 'Ana García',
        'telefono' => '2221234567',
        'correo' => 'ana@example.com',
        'curp' => 'ROGA020101HPLJRN08',
        'password' => 'ClaveSegura2026!',
        'password_confirmation' => 'ClaveSegura2026!',
        'id_rol' => Rol::where('nombre', 'Coordinador')->value('id_rol'),
    ]);

    $usuario = User::where('correo', 'ana@example.com')->firstOrFail();
    $respuesta->assertRedirect(route('alumno.panel'));
    $this->assertAuthenticatedAs($usuario);
    expect($usuario->tieneRol('Alumno'))->toBeTrue();
    expect(Hash::check('ClaveSegura2026!', $usuario->password_hash))->toBeTrue();
    expect($usuario->password_hash)->not->toBe('ClaveSegura2026!');
});

test('el correo y la CURP no pueden duplicarse', function () {
    User::factory()->create(['correo' => 'ana@example.com', 'curp' => 'ROGA020101HPLJRN08']);

    $this->post('/registro', [
        'nombre_completo' => 'Otra Ana', 'telefono' => '2221234567',
        'correo' => 'ana@example.com', 'curp' => 'ROGA020101HPLJRN08',
        'password' => 'ClaveSegura2026!', 'password_confirmation' => 'ClaveSegura2026!',
    ])->assertSessionHasErrors(['correo', 'curp']);
});

test('un alumno puede iniciar sesión con el correo o con el ID secuencial', function () {
    $usuario = User::factory()->create(['correo' => 'ana@example.com']);

    $this->post('/iniciar-sesion', [
        'identificador' => 'ana@example.com', 'password' => 'ClaveDePruebas2026!',
    ])->assertRedirect(route('alumno.panel'));
    $this->assertAuthenticatedAs($usuario);

    $this->post('/cerrar-sesion')->assertRedirect(route('login'));
    $this->assertGuest();

    $this->post('/iniciar-sesion', [
        'identificador' => (string) $usuario->id_usuario, 'password' => 'ClaveDePruebas2026!',
    ])->assertRedirect(route('alumno.panel'));
    $this->assertAuthenticatedAs($usuario);
});

test('el coordinador tiene su panel y un alumno no puede acceder a él', function () {
    $coordinador = User::factory()->coordinador()->create();

    $this->post('/iniciar-sesion', [
        'identificador' => $coordinador->correo, 'password' => 'ClaveDePruebas2026!',
    ])->assertRedirect(route('coordinador.panel'));

    $this->actingAs(User::factory()->create())
        ->get('/coordinador')
        ->assertForbidden();
});

test('una cuenta desactivada no puede iniciar sesión', function () {
    $usuario = User::factory()->inactivo()->create();

    $this->post('/iniciar-sesion', [
        'identificador' => $usuario->correo, 'password' => 'ClaveDePruebas2026!',
    ])->assertSessionHasErrors(['identificador']);

    $this->assertGuest();
});

test('una sesión activa se invalida cuando el coordinador desactiva la cuenta', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario)->get('/alumno')->assertOk();

    $usuario->update(['estado' => 'inactivo']);

    $this->get('/alumno')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('un cambio de rol se aplica antes de autorizar la siguiente ruta', function () {
    $usuario = User::factory()->coordinador()->create();
    $this->actingAs($usuario)->get('/coordinador')->assertOk();

    $usuario->update(['id_rol' => Rol::where('nombre', 'Alumno')->value('id_rol')]);

    $this->get('/coordinador')->assertForbidden();
    $this->get('/alumno')->assertOk();
});
