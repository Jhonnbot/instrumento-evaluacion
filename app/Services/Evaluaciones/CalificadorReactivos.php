<?php

namespace App\Services\Evaluaciones;

use App\Models\Pregunta;

/** Misma clave de corrección para el simulador y el examen oficial. */
final class CalificadorReactivos
{
    public function esCorrecta(Pregunta $pregunta, mixed $respuesta): bool
    {
        if ($respuesta === null) return false;

        $opciones = $pregunta->opciones;
        $correctas = $opciones->where('es_correcta', true)
            ->pluck('id_opcion')->map(fn ($id) => (int) $id)->all();

        if (in_array($pregunta->tipo, ['opcion_multiple', 'verdadero_falso'], true)) {
            return is_int($respuesta) && count($correctas) === 1 && $respuesta === $correctas[0];
        }
        if ($pregunta->tipo === 'seleccion_multiple' && is_array($respuesta)) {
            $marcadas = array_values(array_unique(array_map('intval', $respuesta)));
            sort($marcadas);
            sort($correctas);
            return count($marcadas) > 0 && $marcadas === $correctas;
        }
        if ($pregunta->tipo === 'ordenamiento' && is_array($respuesta) &&
            count($respuesta) === $opciones->count()) {
            return $opciones->every(fn ($opcion) =>
                (int) ($respuesta[(string)$opcion->id_opcion] ?? 0) === (int)$opcion->orden_correcto);
        }

        return false;
    }
}
