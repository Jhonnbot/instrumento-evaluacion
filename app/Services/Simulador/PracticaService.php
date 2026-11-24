<?php

namespace App\Services\Simulador;

use App\Models\Pregunta;
use App\Models\User;
use App\Services\Evaluaciones\CalificadorReactivos;
use App\Support\OpcionesPresentacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/** Practicas temporales: no escriben en intentos ni resultados. */
class PracticaService
{
    public const SESION = 'cocosoft.simulador.actual';

    public const RESULTADO = 'cocosoft.simulador.resultado';

    public function ventana(User $alumno): array
    {
        $global = config('simulador.fecha_inicio_global');
        $inicio = $global ? CarbonImmutable::parse($global, config('app.timezone')) : CarbonImmutable::instance($alumno->created_at);
        $fin = $inicio->addDays((int) config('simulador.dias', 14));
        $ahora = CarbonImmutable::instance(now());

        return [
            'inicio' => $inicio, 'fin' => $fin,
            'disponible' => $ahora->greaterThanOrEqualTo($inicio) && $ahora->lessThan($fin),
        ];
    }

    public function cantidad(): int
    {
        return max(2, min(100, (int) config('simulador.cantidad', 6)));
    }

    public function iniciar(User $alumno): ?array
    {
        $ids = Pregunta::query()->where('estado', true)
            ->whereHas('tema.categoria', fn ($q) => $q->where('estado', true))
            ->has('opciones', '>=', 2)
            ->inRandomOrder()->limit($this->cantidad())
            ->pluck('id_pregunta')->map(fn ($id) => (int) $id)->all();
        if (count($ids) < $this->cantidad()) {
            return null;
        }

        $opciones = Pregunta::with('opciones')->whereIn('id_pregunta', $ids)->get()
            ->mapWithKeys(fn ($p) => [(string) $p->id_pregunta => $p->opciones
                ->sortBy('id_opcion')->pluck('id_opcion')->map(fn ($x) => (int) $x)->all()])->all();

        return ['usuario' => (int) $alumno->getKey(), 'token' => (string) Str::uuid(),
            'ids' => $ids, 'opciones' => $opciones, 'respuestas' => []];
    }

    /** Solo envia campos publicos. Nunca retorna las respuestas correctas. */
    public function bloque(array $sesion, int $pagina): array
    {
        $ids = array_slice($sesion['ids'], ($pagina - 1) * 2, 2);
        $porId = Pregunta::with(['opciones', 'tema.categoria'])->whereIn('id_pregunta', $ids)->get()->keyBy('id_pregunta');
        $salida = [];
        foreach ($ids as $id) {
            $p = $porId->get($id);
            abort_unless($p, 409, 'La pregunta ya no esta disponible. Inicia una nueva práctica.');
            $opciones = $p->opciones->keyBy('id_opcion');
            $visibles = [];
            foreach ($sesion['opciones'][(string) $id] as $oid) {
                if ($o = $opciones->get($oid)) {
                    $visibles[] = ['id' => (int) $oid, 'texto' => $o->texto_opcion];
                }
            }
            $salida[] = ['id' => (int) $id, 'tipo' => $p->tipo, 'enunciado' => $p->enunciado,
                'categoria' => $p->tema->categoria->nombre, 'opciones' => OpcionesPresentacion::preparar($visibles, $p->tipo)];
        }

        return $salida;
    }

    public function guardar(array &$sesion, array $bloque, array $respuestas): void
    {
        foreach ($bloque as $p) {
            $id = (string) $p['id'];
            $valor = $respuestas[$id] ?? null;
            $validos = array_column($p['opciones'], 'id');
            unset($sesion['respuestas'][$id]);
            if (in_array($p['tipo'], ['opcion_multiple', 'verdadero_falso'], true)) {
                if (is_scalar($valor) && ctype_digit((string) $valor) && in_array((int) $valor, $validos, true)) {
                    $sesion['respuestas'][$id] = (int) $valor;
                }
            } elseif ($p['tipo'] === 'seleccion_multiple' && is_array($valor)) {
                $valores = array_values(array_unique(array_map('intval', array_filter($valor,
                    fn ($v) => is_scalar($v) && ctype_digit((string) $v)))));
                if ($valores && ! array_diff($valores, $validos)) {
                    $sesion['respuestas'][$id] = $valores;
                }
            } elseif ($p['tipo'] === 'ordenamiento' && is_array($valor)) {
                $orden = [];
                foreach ($validos as $oid) {
                    $pos = $valor[(string) $oid] ?? null;
                    if (is_scalar($pos) && ctype_digit((string) $pos) && (int) $pos >= 1 && (int) $pos <= count($validos)) {
                        $orden[(string) $oid] = (int) $pos;
                    }
                }
                if ($orden) {
                    $sesion['respuestas'][$id] = $orden;
                }
            }
        }
    }

    public function calificar(array $sesion): array
    {
        $preguntas = Pregunta::with('opciones')->whereIn('id_pregunta', $sesion['ids'])->get()->keyBy('id_pregunta');
        $aciertos = 0;
        $calificador = app(CalificadorReactivos::class);
        foreach ($sesion['ids'] as $id) {
            $pregunta = $preguntas->get($id);
            if ($pregunta && $calificador->esCorrecta($pregunta, $sesion['respuestas'][(string) $id] ?? null)) {
                $aciertos++;
            }
        }
        $total = count($sesion['ids']);

        return ['aciertos' => $aciertos, 'total' => $total,
            'porcentaje' => round(100 * $aciertos / max(1, $total), 2)];
    }
}
