<?php

use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Tema;
use App\Models\User;
use Database\Seeders\RolSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->seed(RolSeeder::class);
});

function hu04Tema(): Tema
{
    $categoria = Categoria::create(['nombre' => 'Conceptos Básicos', 'estado' => true]);

    return Tema::create(['id_categoria' => $categoria->getKey(), 'nombre' => 'Introducción a computadoras']);
}

function hu04Fila(string $codigo, string $tipo, string $pregunta, string $opcion, string $correcta, string $orden = ''): array
{
    return [$codigo, 'Conceptos Básicos', 'Introducción a computadoras', $tipo, $pregunta, $opcion, $correcta, $orden];
}

function hu04Csv(array $filas, string $separador = ','): UploadedFile
{
    $contenido = fopen('php://temp', 'w+');
    fwrite($contenido, "\xEF\xBB\xBF");
    fputcsv($contenido, ['codigo_pregunta', 'categoria', 'tema', 'tipo', 'enunciado', 'texto_opcion', 'es_correcta', 'orden_correcto'], $separador, '"', '');
    foreach ($filas as $fila) {
        fputcsv($contenido, $fila, $separador, '"', '');
    }
    rewind($contenido);
    $datos = stream_get_contents($contenido);
    fclose($contenido);

    return UploadedFile::fake()->createWithContent('preguntas.csv', $datos);
}

test('importación disponible solo para el coordinador', function () {
    $ruta = route('coordinador.banco.importacion.index');
    $this->get($ruta)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get($ruta)->assertForbidden();
    $this->post(route('coordinador.banco.importacion.store'), [
        'archivo' => hu04Csv([]),
    ])->assertForbidden();
    $this->actingAs(User::factory()->coordinador()->create())
        ->get($ruta)->assertOk();
    $this->get(route('coordinador.banco.importacion.plantilla'))->assertOk();
});

test('importa los cuatro tipos y sus opciones con la validación compartida', function () {
    hu04Tema();
    $this->actingAs(User::factory()->coordinador()->create());
    $filas = [
        hu04Fila('P01', 'opcion_multiple', 'Pregunta uno', 'A', '1'),
        hu04Fila('P01', 'opcion_multiple', 'Pregunta uno', 'B', '0'),
        hu04Fila('P02', 'seleccion_multiple', 'Pregunta dos', 'A', '1'),
        hu04Fila('P02', 'seleccion_multiple', 'Pregunta dos', 'B', '1'),
        hu04Fila('P02', 'seleccion_multiple', 'Pregunta dos', 'C', '0'),
        hu04Fila('P03', 'verdadero_falso', 'Pregunta tres', 'Verdadero', '1'),
        hu04Fila('P03', 'verdadero_falso', 'Pregunta tres', 'Falso', '0'),
        hu04Fila('P04', 'ordenamiento', 'Pregunta cuatro', 'Primero', '0', '1'),
        hu04Fila('P04', 'ordenamiento', 'Pregunta cuatro', 'Segundo', '0', '2'),
    ];
    $this->post(route('coordinador.banco.importacion.store'), ['archivo' => hu04Csv($filas)])
        ->assertRedirect(route('coordinador.banco.importacion.index'))
        ->assertSessionHas('reporte_importacion.importadas', 4)
        ->assertSessionHas('reporte_importacion.rechazadas', 0);
    expect(Pregunta::count())->toBe(4);
    expect(Pregunta::withCount('opciones')->get()->pluck('opciones_count')->all())->toBe([2, 3, 2, 2]);
});

test('rechaza reactivos inválidos enteros y conserva el correcto', function () {
    hu04Tema();
    $this->actingAs(User::factory()->coordinador()->create());
    $filas = [
        hu04Fila('VALIDO', 'opcion_multiple', 'Pregunta buena', 'Correcta', '1'),
        hu04Fila('VALIDO', 'opcion_multiple', 'Pregunta buena', 'Incorrecta', '0'),
        hu04Fila('MAL', 'seleccion_multiple', 'Pregunta mala', 'Una', '1'),
        hu04Fila('MAL', 'seleccion_multiple', 'Pregunta mala', 'Dos', '0'),
    ];
    $this->post(route('coordinador.banco.importacion.store'), ['archivo' => hu04Csv($filas)])
        ->assertSessionHas('reporte_importacion.importadas', 1)
        ->assertSessionHas('reporte_importacion.rechazadas', 1);
    expect(Pregunta::count())->toBe(1);
});

test('evita reimportar preguntas existentes con el mismo tema tipo y enunciado', function () {
    hu04Tema();
    $this->actingAs(User::factory()->coordinador()->create());
    $filas = [
        hu04Fila('A1', 'opcion_multiple', 'Pregunta única', 'Correcta', '1'),
        hu04Fila('A1', 'opcion_multiple', 'Pregunta única', 'Incorrecta', '0'),
    ];
    $this->post(route('coordinador.banco.importacion.store'), ['archivo' => hu04Csv($filas)])
        ->assertSessionHas('reporte_importacion.importadas', 1);
    $this->post(route('coordinador.banco.importacion.store'), ['archivo' => hu04Csv($filas)])
        ->assertSessionHas('reporte_importacion.importadas', 0)
        ->assertSessionHas('reporte_importacion.rechazadas', 1);
    expect(Pregunta::count())->toBe(1);
});

test('acepta CSV con punto y coma y marca la categoría inexistente como rechazada', function () {
    hu04Tema();
    $this->actingAs(User::factory()->coordinador()->create());
    $filas = [
        hu04Fila('S1', 'opcion_multiple', 'Pregunta válida', 'Correcta', '1'),
        hu04Fila('S1', 'opcion_multiple', 'Pregunta válida', 'Incorrecta', '0'),
        ['S2', 'Otra categoría', 'Introducción a computadoras', 'opcion_multiple', 'Pregunta inválida', 'Sí', '1', ''],
        ['S2', 'Otra categoría', 'Introducción a computadoras', 'opcion_multiple', 'Pregunta inválida', 'No', '0', ''],
    ];
    $this->post(route('coordinador.banco.importacion.store'), ['archivo' => hu04Csv($filas, ';')])
        ->assertSessionHas('reporte_importacion.importadas', 1)
        ->assertSessionHas('reporte_importacion.rechazadas', 1);
});

test('rechaza una plantilla mal formada sin insertar preguntas', function () {
    hu04Tema();
    $this->actingAs(User::factory()->coordinador()->create());
    $archivo = UploadedFile::fake()->createWithContent('preguntas.csv', "cabecera,distinta\nvalor,dato\n");
    $this->post(route('coordinador.banco.importacion.store'), ['archivo' => $archivo])
        ->assertSessionHasErrors('archivo');
    expect(Pregunta::count())->toBe(0);
});
