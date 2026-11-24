<?php

namespace App\Services\Evaluaciones;

use App\Models\Categoria;
use App\Models\Examen;
use App\Models\Intento;
use App\Models\Pregunta;
use App\Models\User;
use App\Support\OpcionesPresentacion;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Examen oficial: intentos y respuestas persistidos en MySQL, tiempo validado en servidor. */
class ExamenService
{
    public function __construct(private CalificadorReactivos $calificador) {}

    public function examen(): ?Examen
    {
        return Examen::query()->where('activo', true)->orderBy('id_examen')->first();
    }

    public function expira(Intento $intento): CarbonInterface
    {
        return $intento->fecha_inicio->copy()->addMinutes($intento->examen->duracion_minutos);
    }

    public function vencido(Intento $intento): bool
    {
        return now()->greaterThanOrEqualTo($this->expira($intento));
    }

    /** Cuenta intentos por alumno y examen; una aprobación impide otro intento. */
    public function estado(User $alumno, Examen $examen): array
    {
        $this->cerrarVencidosDelAlumno($alumno, $examen);
        $intentos = Intento::query()->where('id_usuario', $alumno->getKey())
            ->where('id_examen', $examen->getKey())->orderByDesc('numero_intento')->get();
        $aprobado = DB::table('resultados')->join('intentos', 'intentos.id_intento', '=', 'resultados.id_intento')
            ->where('intentos.id_usuario', $alumno->getKey())->where('intentos.id_examen', $examen->getKey())
            ->where('resultados.dictamen', 'APROBADO')->exists();
        $activo = $intentos->firstWhere('estado', 'en_curso');
        $usados = $intentos->count();
        $adicional = $usados >= 2 && $this->solicitudAprobadaLibre($alumno) !== null;

        return ['intentos' => $intentos, 'usados' => $usados, 'activo' => $activo,
            'aprobado' => $aprobado, 'puede_iniciar' => ! $aprobado && ! $activo && ($usados < 2 || $adicional),
            'requiere_autorizacion' => ! $aprobado && ! $activo && $usados >= 2 && ! $adicional];
    }

    /** Obtiene autorización administrativa aprobada y aún no consumida, si existe. */
    private function solicitudAprobadaLibre(User $alumno): ?int
    {
        $solicitud = DB::table('solicitudes_intento as s')
            ->leftJoin('intentos as i', 'i.id_solicitud', '=', 's.id_solicitud')
            ->where('s.id_usuario', $alumno->getKey())->where('s.estado', 'aprobada')
            ->whereNull('i.id_intento')->orderBy('s.id_solicitud')->first(['s.id_solicitud']);

        return $solicitud ? (int) $solicitud->id_solicitud : null;
    }

    /** No crea otro intento si existe uno en curso. El bloqueo evita dobles clics simultáneos. */
    public function iniciar(User $alumno, Examen $examen): Intento
    {
        return DB::transaction(function () use ($alumno, $examen): Intento {
            DB::table('usuarios')->where('id_usuario', $alumno->getKey())->lockForUpdate()->first();
            $this->cerrarVencidosDelAlumno($alumno, $examen);
            $estado = $this->estado($alumno, $examen);
            if ($estado['activo']) {
                return $estado['activo']->load('examen');
            }
            if (! $estado['puede_iniciar']) {
                throw ValidationException::withMessages(['examen' => $estado['aprobado'] ? 'Ya aprobaste este examen: no puedes presentar otro intento.' :
                    'Has utilizado tus dos intentos. Necesitas autorización del coordinador para otro.']);
            }
            if ($examen->duracion_minutos !== 50 || $examen->minimo_aprobatorio !== 700 ||
                $examen->preguntas_por_pantalla !== 2) {
                throw ValidationException::withMessages(['examen' => 'La configuración del examen debe ser 50 minutos, mínimo 700 y 2 preguntas por pantalla.']);
            }
            $ids = $this->seleccionar(); // Valida cinco categorías y disponibilidad antes de consumir intento.
            $numero = $estado['usados'] + 1;
            $solicitud = $numero >= 3 ? $this->solicitudAprobadaLibre($alumno) : null;
            $intento = Intento::query()->create([
                'id_usuario' => $alumno->getKey(), 'id_examen' => $examen->getKey(),
                'id_solicitud' => $solicitud, 'numero_intento' => $numero,
                'fecha_inicio' => now(), 'estado' => 'en_curso',
            ]);
            foreach ($ids as $indice => $idPregunta) {
                DB::table('preguntas_intento')->insert([
                    'id_intento' => $intento->getKey(), 'id_pregunta' => $idPregunta,
                    'orden_presentacion' => $indice + 1,
                ]);
            }

            return $intento->load('examen');
        }, 3);
    }

    /** Dos preguntas elegidas por categoría: diez por examen mientras no se fije número oficial. */
    private function seleccionar(): array
    {
        $categorias = Categoria::query()->where('estado', true)->orderBy('id_categoria')->get();
        if ($categorias->count() !== 5) {
            throw ValidationException::withMessages(['banco' => 'El examen oficial requiere exactamente cinco categorías activas (20 % cada una).']);
        }
        $porCategoria = (int) config('examen_cocosoft.preguntas_por_categoria', 2);
        if ($porCategoria < 2 || $porCategoria > 100) {
            throw ValidationException::withMessages(['banco' => 'La cantidad de preguntas por categoría debe ser entre 2 y 100.']);
        }
        $ids = [];
        foreach ($categorias as $categoria) {
            $seleccion = Pregunta::query()->where('estado', true)
                ->whereHas('tema', fn ($consulta) => $consulta->where('id_categoria', $categoria->getKey()))
                ->has('opciones', '>=', 2)->inRandomOrder()->limit($porCategoria)
                ->pluck('id_pregunta')->map(fn ($id) => (int) $id)->all();
            if (count($seleccion) !== $porCategoria) {
                throw ValidationException::withMessages(['banco' => "La categoría «{$categoria->nombre}» necesita al menos {$porCategoria} reactivos activos con opciones."]);
            }
            array_push($ids, ...$seleccion);
        }
        shuffle($ids); // Aleatoriza únicamente el orden de las preguntas, no el de los incisos.

        return $ids;
    }

    public function verificarPropiedad(User $alumno, int $id): Intento
    {
        $intento = Intento::with('examen')->where('id_usuario', $alumno->getKey())->findOrFail($id);
        if ($intento->estado === 'en_curso' && $this->vencido($intento)) {
            $this->finalizar($intento->getKey());
            $intento->refresh();
        }

        return $intento;
    }

    public function paginas(Intento $intento): int
    {
        return (int) ceil(DB::table('preguntas_intento')->where('id_intento', $intento->getKey())->count() / 2);
    }

    /** Vista: recupera las respuestas guardadas sin enviar claves correctas. */
    public function bloque(Intento $intento, int $pagina): array
    {
        $asignadas = DB::table('preguntas_intento')->where('id_intento', $intento->getKey())
            ->orderBy('orden_presentacion')->skip(($pagina - 1) * 2)->take(2)->get();
        $preguntas = Pregunta::with(['opciones', 'tema.categoria'])
            ->whereIn('id_pregunta', $asignadas->pluck('id_pregunta'))->get()->keyBy('id_pregunta');
        $respuestaRows = DB::table('respuestas_intento')->whereIn('id_intento_pregunta', $asignadas->pluck('id_intento_pregunta'))->get();
        $selecciones = DB::table('respuestas_opciones')
            ->whereIn('id_respuesta', $respuestaRows->pluck('id_respuesta'))->get()->groupBy('id_respuesta');
        $respuestaPorPregunta = $respuestaRows->keyBy('id_intento_pregunta');
        $salida = [];
        foreach ($asignadas as $asignada) {
            $pregunta = $preguntas->get($asignada->id_pregunta);
            if (! $pregunta) {
                throw new RuntimeException('No se encontró una pregunta asignada al examen.');
            }
            $opciones = $pregunta->opciones->sortBy('id_opcion')->map(fn ($o) => ['id' => (int) $o->id_opcion, 'texto' => $o->texto_opcion])->values()->all();
            $registro = $respuestaPorPregunta->get($asignada->id_intento_pregunta);
            $guardadas = $registro ? ($selecciones->get($registro->id_respuesta) ?? collect()) : collect();
            $respuesta = match ($pregunta->tipo) {
                'opcion_multiple', 'verdadero_falso' => $guardadas->isEmpty() ? null : (int) $guardadas->first()->id_opcion,
                'seleccion_multiple' => $guardadas->pluck('id_opcion')->map(fn ($v) => (int) $v)->all(),
                'ordenamiento' => $guardadas->mapWithKeys(fn ($r) => [(string) $r->id_opcion => (int) $r->orden_seleccion])->all(),
                default => null,
            };
            $salida[] = ['id' => (int) $asignada->id_pregunta,
                'orden' => (int) $asignada->orden_presentacion, 'enunciado' => $pregunta->enunciado,
                'tipo' => $pregunta->tipo, 'categoria' => $pregunta->tema->categoria->nombre,
                'opciones' => OpcionesPresentacion::preparar($opciones, $pregunta->tipo), 'respuesta' => $respuesta];
        }

        return $salida;
    }

    /** Guarda un bloque atómicamente. Retorna true cuando el intento ha concluido. */
    public function guardar(User $alumno, int $idIntento, int $pagina, array $respuestas, bool $finalizar = false): bool
    {
        return DB::transaction(function () use ($alumno, $idIntento, $pagina, $respuestas, $finalizar): bool {
            $intento = Intento::with('examen')->where('id_usuario', $alumno->getKey())
                ->lockForUpdate()->findOrFail($idIntento);
            if ($intento->estado !== 'en_curso') {
                return true;
            }
            if ($this->vencido($intento)) {
                $this->finalizarBloqueado($intento);

                return true;
            }
            $paginas = $this->paginas($intento);
            abort_unless($pagina >= 1 && $pagina <= $paginas, 404);
            $asignadas = DB::table('preguntas_intento')->where('id_intento', $intento->getKey())
                ->orderBy('orden_presentacion')->skip(($pagina - 1) * 2)->take(2)->get();
            $preguntas = Pregunta::with('opciones')->whereIn('id_pregunta', $asignadas->pluck('id_pregunta'))
                ->get()->keyBy('id_pregunta');
            foreach ($asignadas as $asignada) {
                $pregunta = $preguntas->get($asignada->id_pregunta);
                $valor = $respuestas[(string) $asignada->id_pregunta] ?? null;
                $elecciones = $this->validarRespuesta($pregunta, $valor);
                $existente = DB::table('respuestas_intento')->where('id_intento_pregunta', $asignada->id_intento_pregunta)->first();
                if (! $existente && count($elecciones) === 0) {
                    continue;
                }
                $idRespuesta = $existente ? (int) $existente->id_respuesta : (int) DB::table('respuestas_intento')
                    ->insertGetId(['id_intento_pregunta' => $asignada->id_intento_pregunta,
                        'fecha_respuesta' => now(), 'es_correcta' => null]);
                DB::table('respuestas_opciones')->where('id_respuesta', $idRespuesta)->delete();
                if ($elecciones) {
                    DB::table('respuestas_opciones')->insert(array_map(fn ($opcion) => [
                        'id_respuesta' => $idRespuesta, 'id_opcion' => $opcion['id'],
                        'orden_seleccion' => $opcion['orden'],
                    ], $elecciones));
                }
                DB::table('respuestas_intento')->where('id_respuesta', $idRespuesta)
                    ->update(['fecha_respuesta' => $elecciones ? now() : null, 'es_correcta' => null]);
            }
            if ($finalizar) {
                $this->finalizarBloqueado($intento);

                return true;
            }

            return false;
        }, 3);
    }

    /** @return array<int,array{id:int,orden:?int}> */
    private function validarRespuesta(Pregunta $pregunta, mixed $valor): array
    {
        if ($valor === null || $valor === '' || $valor === []) {
            return [];
        }
        $validos = $pregunta->opciones->pluck('id_opcion')->map(fn ($x) => (int) $x)->all();
        $campo = 'respuestas.'.$pregunta->getKey();
        if (in_array($pregunta->tipo, ['opcion_multiple', 'verdadero_falso'], true)) {
            if (! is_scalar($valor) || ! ctype_digit((string) $valor) || ! in_array((int) $valor, $validos, true)) {
                throw ValidationException::withMessages([$campo => 'Selecciona una opción válida.']);
            }

            return [['id' => (int) $valor, 'orden' => null]];
        }
        if (! is_array($valor)) {
            throw ValidationException::withMessages([$campo => 'Formato de respuesta inválido.']);
        }
        if ($pregunta->tipo === 'seleccion_multiple') {
            $ids = [];
            foreach ($valor as $item) {
                if (! is_scalar($item) || ! ctype_digit((string) $item) || ! in_array((int) $item, $validos, true)) {
                    throw ValidationException::withMessages([$campo => 'Las opciones elegidas deben pertenecer a la pregunta.']);
                }
                $ids[] = (int) $item;
            }
            if (count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages([$campo => 'No se puede seleccionar una opción dos veces.']);
            }

            return array_map(fn ($id) => ['id' => $id, 'orden' => null], $ids);
        }
        if ($pregunta->tipo === 'ordenamiento') {
            $elecciones = [];
            $posiciones = [];
            foreach ($valor as $id => $posicion) {
                if ($posicion === '' || $posicion === null) {
                    continue;
                }
                if (! ctype_digit((string) $id) || ! in_array((int) $id, $validos, true) ||
                    ! is_scalar($posicion) || ! ctype_digit((string) $posicion) ||
                    (int) $posicion < 1 || (int) $posicion > count($validos)) {
                    throw ValidationException::withMessages([$campo => 'Indica posiciones válidas para las opciones de ordenamiento.']);
                }
                $posiciones[] = (int) $posicion;
                $elecciones[] = ['id' => (int) $id, 'orden' => (int) $posicion];
            }
            if (count($posiciones) !== count(array_unique($posiciones))) {
                throw ValidationException::withMessages([$campo => 'No repitas la misma posición de ordenamiento.']);
            }

            return $elecciones;
        }
        throw ValidationException::withMessages([$campo => 'Tipo de reactivo no admitido.']);
    }

    public function finalizar(int $idIntento): void
    {
        DB::transaction(function () use ($idIntento): void {
            $intento = Intento::with('examen')->lockForUpdate()->findOrFail($idIntento);
            if ($intento->estado === 'en_curso') {
                $this->finalizarBloqueado($intento);
            }
        }, 3);
    }

    /** Llamar solo dentro de una transacción y con bloqueo de la fila intento. */
    private function finalizarBloqueado(Intento $intento): void
    {
        if ($intento->estado !== 'en_curso') {
            return;
        }
        $asignadas = DB::table('preguntas_intento')->where('id_intento', $intento->getKey())->get();
        $preguntas = Pregunta::with(['opciones', 'tema.categoria'])
            ->whereIn('id_pregunta', $asignadas->pluck('id_pregunta'))->get()->keyBy('id_pregunta');
        $rRows = DB::table('respuestas_intento')->whereIn('id_intento_pregunta', $asignadas->pluck('id_intento_pregunta'))->get();
        $selecciones = DB::table('respuestas_opciones')->whereIn('id_respuesta', $rRows->pluck('id_respuesta'))
            ->get()->groupBy('id_respuesta');
        $rPorAsignada = $rRows->keyBy('id_intento_pregunta');
        $categorias = [];
        $temas = [];
        foreach ($asignadas as $asignada) {
            $pregunta = $preguntas->get($asignada->id_pregunta);
            if (! $pregunta) {
                throw new RuntimeException('Pregunta asignada inexistente. No es posible cerrar el examen.');
            }
            $cid = (int) $pregunta->tema->id_categoria;
            $tid = (int) $pregunta->id_tema;
            $categorias[$cid] ??= ['total' => 0, 'aciertos' => 0];
            $temas[$tid] ??= ['total' => 0, 'aciertos' => 0];
            $registro = $rPorAsignada->get($asignada->id_intento_pregunta);
            $marcadas = $registro ? ($selecciones->get($registro->id_respuesta) ?? collect()) : collect();
            $valor = match ($pregunta->tipo) {
                'opcion_multiple','verdadero_falso' => $marcadas->isEmpty() ? null : (int) $marcadas->first()->id_opcion,
                'seleccion_multiple' => $marcadas->pluck('id_opcion')->map(fn ($id) => (int) $id)->all(),
                'ordenamiento' => $marcadas->mapWithKeys(fn ($r) => [(string) $r->id_opcion => (int) $r->orden_seleccion])->all(),
                default => null,
            };
            $bien = $this->calificador->esCorrecta($pregunta, $valor);
            $categorias[$cid]['total']++;
            $temas[$tid]['total']++;
            if ($bien) {
                $categorias[$cid]['aciertos']++;
                $temas[$tid]['aciertos']++;
            }
            if ($registro) {
                DB::table('respuestas_intento')->where('id_respuesta', $registro->id_respuesta)
                    ->update(['es_correcta' => $bien]);
            }
        }
        if (count($categorias) !== 5) {
            throw new RuntimeException('El examen necesita cinco categorías distintas para calificar.');
        }
        $puntaje = 0.0;
        foreach ($categorias as $categoria) {
            $puntaje += round(200 * $categoria['aciertos'] / $categoria['total'], 2);
        }
        $total = (int) round($puntaje);
        $dictamen = $total >= 700 ? 'APROBADO' : 'REPROBADO';
        $idResultado = DB::table('resultados')->insertGetId([
            'id_intento' => $intento->getKey(), 'calificacion_final' => $total,
            'dictamen' => $dictamen, 'fecha_calificacion' => now(),
        ]);
        foreach ($categorias as $id => $c) {
            DB::table('detalle_resultado_categoria')->insert([
                'id_resultado' => $idResultado, 'id_categoria' => $id,
                'puntaje_obtenido' => round(200 * $c['aciertos'] / $c['total'], 2),
                'ponderacion_aplicada' => 0.2,
            ]);
        }
        foreach ($temas as $id => $t) {
            DB::table('detalle_resultado_tema')->insert([
                'id_resultado' => $idResultado, 'id_tema' => $id,
                'preguntas_totales' => $t['total'], 'respuestas_correctas' => $t['aciertos'],
            ]);
        }
        $caducado = $this->vencido($intento);
        $intento->update([
            'estado' => $caducado ? 'tiempo_agotado' : 'enviado',
            'fecha_fin' => $caducado ? $this->expira($intento) : now(),
        ]);
    }

    public function cerrarVencidosDelAlumno(User $alumno, Examen $examen): void
    {
        $activos = Intento::with('examen')->where('id_usuario', $alumno->getKey())
            ->where('id_examen', $examen->getKey())->where('estado', 'en_curso')->get();
        foreach ($activos as $intento) {
            if ($this->vencido($intento)) {
                $this->finalizar($intento->getKey());
            }
        }
    }

    /** Cierre desatendido para la tarea programada, sin depender del navegador. */
    public function cerrarVencidos(): int
    {
        $cerrados = 0;
        Intento::with('examen')->where('estado', 'en_curso')->orderBy('id_intento')
            ->chunkById(50, function ($intentos) use (&$cerrados): void {
                foreach ($intentos as $intento) {
                    if ($this->vencido($intento)) {
                        $this->finalizar($intento->getKey());
                        $cerrados++;
                    }
                }
            }, 'id_intento');

        return $cerrados;
    }
}
