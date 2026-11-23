<?php

namespace App\Services\BancoPreguntas;

use App\Models\Pregunta;
use App\Models\Tema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Reglas de HU-05 compartidas por el formulario manual y la importación HU-04. */
final class ReglasPregunta
{
    public static function rules(): array
    {
        return [
            'id_tema' => ['required', 'integer', Rule::exists('temas', 'id_tema')],
            'enunciado' => ['required', 'string', 'max:10000'],
            'tipo' => ['required', Rule::in(array_keys(Pregunta::TIPOS))],
            'opciones' => ['required', 'array', 'min:2', 'max:20'],
            'opciones.*' => ['required', 'array:texto_opcion,es_correcta,orden_correcto'],
            'opciones.*.texto_opcion' => ['required', 'string', 'max:2000'],
            'opciones.*.es_correcta' => ['sometimes', 'boolean'],
            'opciones.*.orden_correcto' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public static function comprobar(Validator $validator, array $datos): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $tema = Tema::with('categoria')->find($datos['id_tema']);
        if (! $tema?->categoria?->estado) {
            $validator->errors()->add('id_tema', 'El tema debe pertenecer a una categoría activa.');
            return;
        }

        $opciones = array_values($datos['opciones']);
        $tipo = $datos['tipo'];
        $correctas = collect($opciones)->filter(fn ($o) => (bool) ($o['es_correcta'] ?? false))->count();

        if ($tipo !== 'ordenamiento' && collect($opciones)->contains(fn ($o) => filled($o['orden_correcto'] ?? null))) {
            $validator->errors()->add('opciones', 'El orden correcto solo se permite en preguntas de ordenamiento.');
        }
        if ($tipo === 'opcion_multiple' && $correctas !== 1) {
            $validator->errors()->add('opciones', 'Opción múltiple requiere exactamente una respuesta correcta.');
        }
        if ($tipo === 'seleccion_multiple' && $correctas < 2) {
            $validator->errors()->add('opciones', 'Selección múltiple requiere al menos dos respuestas correctas.');
        }
        if ($tipo === 'verdadero_falso') {
            $etiquetas = collect($opciones)->map(fn ($o) => mb_strtolower($o['texto_opcion']))->sort()->values()->all();
            if (count($opciones) !== 2 || $etiquetas !== ['falso', 'verdadero'] || $correctas !== 1) {
                $validator->errors()->add('opciones', 'Verdadero/Falso debe contener exactamente las opciones Verdadero y Falso, con una correcta.');
            }
        }
        if ($tipo === 'ordenamiento') {
            if ($correctas !== 0) {
                $validator->errors()->add('opciones', 'En ordenamiento la respuesta correcta se determina por la posición, no por casillas.');
            }
            $orden = array_map(fn ($o) => (int) ($o['orden_correcto'] ?? 0), $opciones);
            sort($orden);
            if ($orden !== range(1, count($opciones))) {
                $validator->errors()->add('opciones', 'El orden correcto debe contener todos los números de 1 a '.count($opciones).', sin repetirse.');
            }
        }

        $textos = collect($opciones)->map(fn ($o) => mb_strtolower(trim($o['texto_opcion'])));
        if ($textos->unique()->count() !== $textos->count()) {
            $validator->errors()->add('opciones', 'Las opciones de respuesta no deben repetirse dentro de una pregunta.');
        }
    }
}
