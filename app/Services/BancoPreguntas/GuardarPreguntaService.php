<?php

namespace App\Services\BancoPreguntas;

use App\Models\Pregunta;
use Illuminate\Support\Facades\DB;

/** Guarda en una sola transacción la pregunta y TODAS sus opciones. */
class GuardarPreguntaService
{
    public function crear(array $datos): Pregunta
    {
        return DB::transaction(function () use ($datos): Pregunta {
            $pregunta = Pregunta::create([
                'id_tema' => $datos['id_tema'],
                'enunciado' => $datos['enunciado'],
                'tipo' => $datos['tipo'],
                'estado' => true,
            ]);
            $pregunta->opciones()->createMany($this->prepararOpciones($datos));

            return $pregunta;
        });
    }

    public function actualizar(Pregunta $pregunta, array $datos): void
    {
        DB::transaction(function () use ($pregunta, $datos): void {
            $bloqueada = Pregunta::query()->lockForUpdate()->findOrFail($pregunta->getKey());
            // No cambiar el reactivo que podría estar en un examen o en un historial.
            abort_if($bloqueada->preguntasIntento()->exists(), 409, 'Este reactivo ya está vinculado a un examen; solo se puede desactivar.');

            $bloqueada->update([
                'id_tema' => $datos['id_tema'],
                'enunciado' => $datos['enunciado'],
                'tipo' => $datos['tipo'],
            ]);
            $bloqueada->opciones()->delete();
            $bloqueada->opciones()->createMany($this->prepararOpciones($datos));
        });
    }

    private function prepararOpciones(array $datos): array
    {
        return array_map(fn (array $opcion): array => [
            'texto_opcion' => $opcion['texto_opcion'],
            'es_correcta' => $datos['tipo'] !== 'ordenamiento' && (bool) ($opcion['es_correcta'] ?? false),
            'orden_correcto' => $datos['tipo'] === 'ordenamiento' ? (int) $opcion['orden_correcto'] : null,
        ], array_values($datos['opciones']));
    }
}
