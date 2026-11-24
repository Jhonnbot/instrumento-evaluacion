<?php

use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Tema;
use App\Models\User;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RolSeeder::class);
});

function hu05Catalogo(): Tema
{
    $categoria = Categoria::create(['nombre' => 'Categoría de prueba', 'estado' => true]);

    return Tema::create(['id_categoria' => $categoria->getKey(), 'nombre' => 'Tema de prueba']);
}

function hu05Datos(Tema $tema, string $tipo = 'opcion_multiple'): array
{
    $opciones = match ($tipo) {
        'opcion_multiple' => [
            ['texto_opcion' => 'A', 'es_correcta' => '1'],
            ['texto_opcion' => 'B'],
        ],
        'seleccion_multiple' => [
            ['texto_opcion' => 'A', 'es_correcta' => '1'],
            ['texto_opcion' => 'B', 'es_correcta' => '1'],
            ['texto_opcion' => 'C'],
        ],
        'verdadero_falso' => [
            ['texto_opcion' => 'Verdadero', 'es_correcta' => '1'],
            ['texto_opcion' => 'Falso'],
        ],
        'ordenamiento' => [
            ['texto_opcion' => 'Primero', 'orden_correcto' => 1],
            ['texto_opcion' => 'Segundo', 'orden_correcto' => 2],
        ],
    };

    return [
        'id_tema' => $tema->getKey(),
        'enunciado' => 'Ejemplo de reactivo '.$tipo,
        'tipo' => $tipo,
        'opciones' => $opciones,
    ];
}

test('solo un coordinador puede administrar el banco de preguntas', function () {
    $this->get(route('coordinador.banco.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())
        ->get(route('coordinador.banco.index'))->assertForbidden();
    $this->get(route('coordinador.banco.categorias.index'))->assertForbidden();
    $this->post(route('coordinador.banco.categorias.store'), ['nombre' => 'Prueba'])->assertForbidden();
    $this->actingAs(User::factory()->coordinador()->create())
        ->get(route('coordinador.banco.index'))->assertOk();
});

test('solo admite cinco categorias y todas pesan 20 por ciento', function () {
    $this->actingAs(User::factory()->coordinador()->create());
    for ($i = 1; $i <= 5; $i++) {
        $this->post(route('coordinador.banco.categorias.store'), ['nombre' => 'Categoría '.$i])
            ->assertSessionHasNoErrors();
    }
    expect(Categoria::count())->toBe(5);
    expect(Categoria::query()->where('ponderacion', '0.2000')->count())->toBe(5);
    $this->post(route('coordinador.banco.categorias.store'), ['nombre' => 'Categoría 6'])
        ->assertSessionHasErrors('nombre');
    expect(Categoria::count())->toBe(5);
});

test('puede crear un tema pero no duplicarlo en su categoria', function () {
    $this->actingAs(User::factory()->coordinador()->create());
    $categoria = Categoria::create(['nombre' => 'Fundamentos']);
    $datos = ['id_categoria' => $categoria->getKey(), 'nombre' => 'Lógica básica', 'descripcion' => 'Conceptos'];
    $this->post(route('coordinador.banco.temas.store'), $datos)->assertSessionHasNoErrors();
    $this->post(route('coordinador.banco.temas.store'), $datos)->assertSessionHasErrors('nombre');
    expect(Tema::count())->toBe(1);
});

test('guarda los cuatro tipos con su estructura valida', function (string $tipo, int $correctas) {
    $this->actingAs(User::factory()->coordinador()->create());
    $tema = hu05Catalogo();
    $this->post(route('coordinador.banco.preguntas.store'), hu05Datos($tema, $tipo))
        ->assertRedirect(route('coordinador.banco.index'));
    $pregunta = Pregunta::with('opciones')->firstOrFail();
    expect($pregunta->tipo)->toBe($tipo);
    expect($pregunta->opciones->where('es_correcta', true)->count())->toBe($correctas);
    if ($tipo === 'ordenamiento') {
        expect($pregunta->opciones->pluck('orden_correcto')->sort()->values()->all())->toBe([1, 2]);
    }
})->with([
    ['opcion_multiple', 1],
    ['seleccion_multiple', 2],
    ['verdadero_falso', 1],
    ['ordenamiento', 0],
]);

test('rechaza respuestas incorrectas o posiciones repetidas', function () {
    $this->actingAs(User::factory()->coordinador()->create());
    $tema = hu05Catalogo();
    $datos = hu05Datos($tema);
    unset($datos['opciones'][0]['es_correcta']);
    $this->post(route('coordinador.banco.preguntas.store'), $datos)
        ->assertSessionHasErrors('opciones');

    $datos = hu05Datos($tema, 'ordenamiento');
    $datos['opciones'][1]['orden_correcto'] = 1;
    $this->post(route('coordinador.banco.preguntas.store'), $datos)
        ->assertSessionHasErrors('opciones');
    expect(Pregunta::count())->toBe(0);
});

test('actualiza las opciones de un reactivo sin examen y conserva el ID', function () {
    $this->actingAs(User::factory()->coordinador()->create());
    $tema = hu05Catalogo();
    $this->post(route('coordinador.banco.preguntas.store'), hu05Datos($tema));
    $pregunta = Pregunta::firstOrFail();
    $datos = hu05Datos($tema, 'verdadero_falso');
    $this->put(route('coordinador.banco.preguntas.update', $pregunta), $datos)
        ->assertRedirect(route('coordinador.banco.index'));
    expect($pregunta->fresh()->tipo)->toBe('verdadero_falso');
    expect($pregunta->fresh()->opciones()->count())->toBe(2);
    expect(Pregunta::count())->toBe(1);
});

test('no modifica preguntas usadas en examen pero permite desactivarlas', function () {
    $coordinador = User::factory()->coordinador()->create();
    $alumno = User::factory()->create();
    $this->actingAs($coordinador);
    $tema = hu05Catalogo();
    $this->post(route('coordinador.banco.preguntas.store'), hu05Datos($tema));
    $pregunta = Pregunta::firstOrFail();

    $idExamen = DB::table('examenes')->insertGetId([
        'nombre' => 'Evaluación de prueba', 'duracion_minutos' => 50,
        'minimo_aprobatorio' => 700, 'preguntas_por_pantalla' => 2, 'activo' => true,
    ], 'id_examen');
    $idIntento = DB::table('intentos')->insertGetId([
        'id_usuario' => $alumno->getKey(), 'id_examen' => $idExamen,
        'numero_intento' => 1, 'estado' => 'en_curso', 'fecha_inicio' => now(),
    ], 'id_intento');
    DB::table('preguntas_intento')->insert([
        'id_intento' => $idIntento, 'id_pregunta' => $pregunta->getKey(), 'orden_presentacion' => 1,
    ]);

    $this->get(route('coordinador.banco.preguntas.edit', $pregunta))->assertStatus(409);
    $this->put(route('coordinador.banco.preguntas.update', $pregunta), hu05Datos($tema))->assertStatus(409);
    $this->patch(route('coordinador.banco.preguntas.estado', $pregunta), ['estado' => '0'])
        ->assertRedirect();
    expect($pregunta->fresh()->estado)->toBeFalse();
});
