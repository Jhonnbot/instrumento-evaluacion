<?php

namespace App\Services\Evaluaciones;

use App\Models\Intento;
use App\Support\FechaMexico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Consultas de resultados oficiales; no incorpora prácticas ni intentos sin calificación. */
class HistorialResultadosService
{
    public function consultar(array $filtros, ?int $idAlumno = null): Builder
    {
        $consulta = Intento::query()->with(['alumno', 'examen', 'resultado'])
            ->whereHas('resultado');

        if ($idAlumno !== null) {
            $consulta->where('id_usuario', $idAlumno);
        }

        if (($dictamen = $filtros['dictamen'] ?? '') !== '') {
            $consulta->whereHas('resultado', fn (Builder $q) => $q->where('dictamen', $dictamen));
        }

        if (($desde = $filtros['desde'] ?? '') !== '') {
            $consulta->where('fecha_inicio', '>=', FechaMexico::inicioDiaUtc($desde));
        }
        if (($hasta = $filtros['hasta'] ?? '') !== '') {
            $consulta->where('fecha_inicio', '<', FechaMexico::finDiaExclusivoUtc($hasta));
        }

        // La búsqueda por alumno solo se habilita en el historial general.
        if ($idAlumno === null && ($buscar = trim($filtros['buscar'] ?? '')) !== '') {
            $consulta->whereHas('alumno', function (Builder $q) use ($buscar): void {
                $q->where(function (Builder $alumno) use ($buscar): void {
                    $alumno->where('nombre_completo', 'like', '%'.$buscar.'%')
                        ->orWhere('correo', 'like', '%'.$buscar.'%');
                    if (ctype_digit($buscar)) {
                        $alumno->orWhere('id_usuario', (int) $buscar);
                    }
                });
            });
        }

        return $consulta->orderByDesc('fecha_inicio')->orderByDesc('id_intento');
    }

    public function detalle(Intento $intento): array
    {
        $idResultado = $intento->resultado->getKey();
        $categorias = DB::table('detalle_resultado_categoria as d')
            ->join('categorias as c', 'c.id_categoria', '=', 'd.id_categoria')
            ->where('d.id_resultado', $idResultado)
            ->orderBy('c.id_categoria')
            ->get(['c.nombre', 'd.puntaje_obtenido', 'd.ponderacion_aplicada']);
        $temas = DB::table('detalle_resultado_tema as d')
            ->join('temas as t', 't.id_tema', '=', 'd.id_tema')
            ->join('categorias as c', 'c.id_categoria', '=', 't.id_categoria')
            ->where('d.id_resultado', $idResultado)
            ->orderBy('c.id_categoria')->orderBy('t.id_tema')
            ->get(['c.nombre as categoria', 't.nombre as tema',
                'd.preguntas_totales', 'd.respuestas_correctas']);

        return compact('categorias', 'temas');
    }
}
