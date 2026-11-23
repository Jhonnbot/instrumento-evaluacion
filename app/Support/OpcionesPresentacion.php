<?php

namespace App\Support;

/** Ordena y rotula opciones sin cambiar sus IDs o la clave de respuesta en MySQL. */
final class OpcionesPresentacion
{
    /**
     * @param array<int,array{id:int,texto:string}> $opciones
     * @return array<int,array{id:int,texto:string}>
     */
    public static function preparar(array $opciones, string $tipo): array
    {
        // Respetar las letras que ya estaban escritas, si son consecutivas y únicas.
        $letras = [];
        foreach ($opciones as $opcion) {
            if (!preg_match('/^\s*([a-z])\s*[).:-]\s*/iu', $opcion['texto'], $match)) {
                $letras = [];
                break;
            }
            $letras[] = strtolower($match[1]);
        }
        $esperadas = array_map(fn ($n) => chr(97 + $n), range(0, count($opciones) - 1));
        if ($letras && count(array_unique($letras)) === count($opciones) &&
            array_diff($letras, $esperadas) === [] && array_diff($esperadas, $letras) === []) {
            usort($opciones, function (array $a, array $b): int {
                preg_match('/^\s*([a-z])\s*[).:-]/iu', $a['texto'], $ma);
                preg_match('/^\s*([a-z])\s*[).:-]/iu', $b['texto'], $mb);
                return strcmp(strtolower($ma[1]), strtolower($mb[1]));
            });
        } else {
            usort($opciones, fn ($a, $b) => $a['id'] <=> $b['id']);
        }

        // Las opciones ya etiquetadas no deben mostrar letras duplicadas (a) a) ...).
        // Los números 1., 2., etc. en textos de ordenamiento pueden revelar la respuesta;
        // se retiran solo cuando todas las opciones contienen dichos marcadores.
        $marcasOrden = $tipo === 'ordenamiento' && count($opciones) > 0;
        if ($marcasOrden) {
            foreach ($opciones as $opcion) {
                if (!preg_match('/^\s*\d{1,2}\s*[).:-]?\s+\S/u', $opcion['texto'])) {
                    $marcasOrden = false;
                    break;
                }
            }
        }
        foreach ($opciones as $i => &$opcion) {
            $texto = trim($opcion['texto']);
            $texto = preg_replace('/^([a-z])\s*[).:-]\s*/iu', '', $texto) ?? $texto;
            if ($marcasOrden) {
                $texto = preg_replace('/^\d{1,2}\s*[).:-]?\s+/u', '', $texto) ?? $texto;
            }
            $opcion['texto'] = chr(97 + $i).') '.$texto;
        }
        unset($opcion);

        return $opciones;
    }
}
