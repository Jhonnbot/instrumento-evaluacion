<?php
use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Tema;
use App\Models\User;
use App\Services\Simulador\PracticaService;
use Database\Seeders\RolSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolSeeder::class);
    config()->set('simulador.fecha_inicio_global', null);
    config()->set('simulador.cantidad', 4);
});

function hu06Banco(): array
{
    $categoria = Categoria::create(['nombre'=>'Categoría simulador','estado'=>true]);
    $tema = Tema::create(['id_categoria'=>$categoria->id_categoria,'nombre'=>'Tema simulador']);
    $tipos = [
        'opcion_multiple' => [
            ['texto_opcion'=>'Correcta','es_correcta'=>true],
            ['texto_opcion'=>'Incorrecta','es_correcta'=>false]],
        'seleccion_multiple' => [
            ['texto_opcion'=>'Uno','es_correcta'=>true],
            ['texto_opcion'=>'Dos','es_correcta'=>true],
            ['texto_opcion'=>'Tres','es_correcta'=>false]],
        'verdadero_falso' => [
            ['texto_opcion'=>'Verdadero','es_correcta'=>true],
            ['texto_opcion'=>'Falso','es_correcta'=>false]],
        'ordenamiento' => [
            ['texto_opcion'=>'Primero','orden_correcto'=>1],
            ['texto_opcion'=>'Segundo','orden_correcto'=>2],
            ['texto_opcion'=>'Tercero','orden_correcto'=>3]],
    ];
    $creadas = [];
    foreach ($tipos as $tipo=>$opciones) {
        $p = Pregunta::create(['id_tema'=>$tema->id_tema,'tipo'=>$tipo,'enunciado'=>'Pregunta '.$tipo,'estado'=>true]);
        $p->opciones()->createMany($opciones);
        $creadas[$tipo] = $p;
    }
    return $creadas;
}

test('solo el alumno autenticado puede acceder al simulador', function () {
    $this->get(route('alumno.simulador.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->coordinador()->create())
        ->get(route('alumno.simulador.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())
        ->get(route('alumno.simulador.index'))->assertOk();
});

test('no inicia si faltan preguntas activas y si las hay inicia', function () {
    $this->actingAs(User::factory()->create());
    $this->post(route('alumno.simulador.iniciar'))->assertRedirect(route('alumno.simulador.index'));
    expect(session()->has(PracticaService::SESION))->toBeFalse();
    hu06Banco();
    $this->post(route('alumno.simulador.iniciar'))->assertRedirect(route('alumno.simulador.preguntas',1));
    expect(session()->get(PracticaService::SESION)['ids'])->toHaveCount(4);
});

test('muestra dos preguntas por pagina sin revelar las claves', function () {
    hu06Banco();
    $this->actingAs(User::factory()->create())->post(route('alumno.simulador.iniciar'));
    $this->get(route('alumno.simulador.preguntas',1))
        ->assertOk()->assertSee('Página 1 de 2')->assertDontSee('es_correcta')->assertDontSee('orden_correcto');
    $this->get(route('alumno.simulador.preguntas',2))->assertOk()->assertSee('Página 2 de 2');
    $this->get(route('alumno.simulador.preguntas',3))->assertNotFound();
});

test('califica los cuatro tipos y no crea historial oficial', function () {
    hu06Banco();
    $this->actingAs(User::factory()->create())->post(route('alumno.simulador.iniciar'));
    $s = session()->get(PracticaService::SESION);
    $r = [];
    foreach ($s['ids'] as $id) {
        $p = Pregunta::with('opciones')->findOrFail($id);
        $r[$id] = match ($p->tipo) {
            'opcion_multiple','verdadero_falso' => $p->opciones->firstWhere('es_correcta',true)->id_opcion,
            'seleccion_multiple' => $p->opciones->where('es_correcta',true)->pluck('id_opcion')->all(),
            'ordenamiento' => $p->opciones->mapWithKeys(fn($o)=>[(string)$o->id_opcion=>$o->orden_correcto])->all(),
        };
    }
    $this->post(route('alumno.simulador.guardar',1), [
        'accion'=>'siguiente', 'token'=>$s['token'],
        'respuestas'=>array_intersect_key($r,array_flip(array_slice($s['ids'],0,2)))
    ])->assertRedirect(route('alumno.simulador.preguntas',2));
    $this->post(route('alumno.simulador.guardar',2), [
        'accion'=>'finalizar', 'token'=>$s['token'],
        'respuestas'=>array_intersect_key($r,array_flip(array_slice($s['ids'],2,2)))
    ])->assertRedirect(route('alumno.simulador.resultado'));
    expect(session()->has(PracticaService::SESION))->toBeFalse();
    $this->get(route('alumno.simulador.resultado'))->assertOk()->assertSee('4 de 4')->assertSee('100.00');
    expect(DB::table('intentos')->count())->toBe(0);
    expect(DB::table('resultados')->count())->toBe(0);
});

test('no permite continuar al vencer los catorce dias', function () {
    hu06Banco();
    $this->actingAs(User::factory()->create())->post(route('alumno.simulador.iniciar'))
        ->assertRedirect(route('alumno.simulador.preguntas',1));
    $this->travel(15)->days();
    $this->get(route('alumno.simulador.preguntas',1))->assertRedirect(route('alumno.simulador.index'));
    $this->post(route('alumno.simulador.iniciar'))->assertRedirect(route('alumno.simulador.index'));
    expect(session()->has(PracticaService::SESION))->toBeFalse();
});

test('permite otro intento de practica sin crear intentos de examen', function () {
    hu06Banco();
    $this->actingAs(User::factory()->create());
    $this->post(route('alumno.simulador.iniciar'));
    $primero = session()->get(PracticaService::SESION)['token'];
    $this->post(route('alumno.simulador.iniciar'));
    $nuevo = session()->get(PracticaService::SESION)['token'];
    expect($nuevo)->not->toBe($primero);
    expect(DB::table('intentos')->count())->toBe(0);
    $this->post(route('alumno.simulador.guardar',1), [
        'accion'=>'siguiente','token'=>$primero
    ])->assertStatus(409);
});
