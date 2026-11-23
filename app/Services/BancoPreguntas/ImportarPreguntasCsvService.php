<?php

namespace App\Services\BancoPreguntas;

use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Tema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Throwable;

/**
 * HU-04: importa CSV UTF-8 con una fila por opción.
 * Una pregunta inválida se rechaza completa; las válidas se guardan de forma independiente.
 * No crea categorías/temas y nunca sobreescribe preguntas existentes.
 */
final class ImportarPreguntasCsvService
{
    private const CABECERAS = [
        'codigo_pregunta', 'categoria', 'tema', 'tipo',
        'enunciado', 'texto_opcion', 'es_correcta', 'orden_correcto',
    ];

    private const MAX_FILAS = 5000;
    private const MAX_PREGUNTAS = 1000;
    private const MAX_ERRORES_VISIBLES = 80;

    public function __construct(private GuardarPreguntaService $guardador) {}

    public function importar(string $ruta): array
    {
        if (! is_file($ruta) || ! is_readable($ruta)) {
            throw new InvalidArgumentException('No se pudo leer el archivo CSV recibido.');
        }

        $archivo = fopen($ruta, 'rb');
        if ($archivo === false) {
            throw new InvalidArgumentException('No se pudo abrir el archivo CSV.');
        }

        try {
            $agrupadas = $this->leerAgrupadas($archivo);
        } finally {
            fclose($archivo);
        }

        $reporte = [
            'preguntas_detectadas' => count($agrupadas),
            'importadas' => 0,
            'rechazadas' => 0,
            'errores' => [],
        ];
        $huellas = [];

        foreach ($agrupadas as $codigo => $grupo) {
            $fallos = $grupo['errores'];
            $etiqueta = 'Código '.$codigo.' (registro CSV '.$grupo['registro'].')';

            $categoria = null;
            $tema = null;
            if ($fallos === []) {
                $categoria = Categoria::where('nombre', $grupo['categoria'])->first();
                if (! $categoria || ! $categoria->estado) {
                    $fallos[] = 'La categoría no existe o está inactiva: '.$grupo['categoria'];
                } else {
                    $tema = Tema::where('id_categoria', $categoria->getKey())
                        ->where('nombre', $grupo['tema'])->first();
                    if (! $tema) {
                        $fallos[] = 'El tema no pertenece a esa categoría: '.$grupo['tema'];
                    }
                }
            }

            if ($fallos === [] && $tema) {
                $datos = [
                    'id_tema' => $tema->getKey(),
                    'enunciado' => $grupo['enunciado'],
                    'tipo' => $grupo['tipo'],
                    'opciones' => $grupo['opciones'],
                ];
                $validador = Validator::make($datos, ReglasPregunta::rules());
                $validador->after(fn ($v) => ReglasPregunta::comprobar($v, $datos));

                if ($validador->fails()) {
                    $fallos = $validador->errors()->all();
                } else {
                    $huella = $this->huella($datos);
                    if (isset($huellas[$huella])) {
                        $fallos[] = 'Reactivo repetido en este archivo (código '.$huellas[$huella].').';
                    } elseif (Pregunta::where('id_tema', $datos['id_tema'])
                        ->where('tipo', $datos['tipo'])->where('enunciado', $datos['enunciado'])->exists()) {
                        $fallos[] = 'Ya existe una pregunta con el mismo tema, tipo y enunciado.';
                    } else {
                        try {
                            $this->guardador->crear($validador->validated());
                            $huellas[$huella] = $codigo;
                            $reporte['importadas']++;
                        } catch (Throwable $e) {
                            Log::error('Error al importar pregunta CSV', ['codigo' => $codigo, 'exception' => $e]);
                            $fallos[] = 'No pudo guardarse. Revisa el registro de errores de Laravel.';
                        }
                    }
                }
            }

            if ($fallos !== []) {
                $reporte['rechazadas']++;
                if (count($reporte['errores']) < self::MAX_ERRORES_VISIBLES) {
                    $reporte['errores'][] = ['codigo' => $etiqueta, 'detalle' => implode(' ', $fallos)];
                }
            }
        }

        return $reporte;
    }

    private function leerAgrupadas($archivo): array
    {
        $cabecera = fgetcsv($archivo, 0, ',', '"', '');
        if ($cabecera === false || $cabecera === [null]) {
            throw new InvalidArgumentException('El archivo CSV está vacío.');
        }
        $cabecera[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecera[0]);
        $separador = ',';
        if ($this->normalizarCabeceras($cabecera) !== self::CABECERAS) {
            rewind($archivo);
            $cabecera = fgetcsv($archivo, 0, ';', '"', '');
            $cabecera[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($cabecera[0] ?? ''));
            $separador = ';';
        }
        if ($this->normalizarCabeceras($cabecera) !== self::CABECERAS) {
            throw new InvalidArgumentException('Las columnas no corresponden a la plantilla. Usa CSV UTF-8 con las 8 cabeceras oficiales, separadas por coma o punto y coma.');
        }

        $grupos = [];
        $numeroRegistro = 1; // Primera fila: encabezado
        while (($fila = fgetcsv($archivo, 0, $separador, '"', '')) !== false) {
            $numeroRegistro++;
            if ($fila === [null] || $fila === ['']) {
                continue;
            }
            if ($numeroRegistro > self::MAX_FILAS + 1) {
                throw new InvalidArgumentException('El archivo supera el límite de '.self::MAX_FILAS.' registros CSV. Divídelo en varios archivos.');
            }
            if (count($fila) !== count(self::CABECERAS)) {
                $codigoInvalido = '#registro-'.$numeroRegistro;
                $grupos[$codigoInvalido] = [
                    'registro' => $numeroRegistro, 'errores' => ['Número de columnas incorrecto; se esperan 8.'],
                    'categoria' => '', 'tema' => '', 'tipo' => '', 'enunciado' => '', 'opciones' => [],
                ];
                continue;
            }
            if (! mb_check_encoding(implode('', $fila), 'UTF-8')) {
                throw new InvalidArgumentException('El archivo debe guardarse en codificación UTF-8.');
            }
            $datosFila = array_combine(self::CABECERAS, array_map('trim', $fila));
            $codigo = $datosFila['codigo_pregunta'];
            if ($codigo === '' || ! preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $codigo)) {
                $codigo = '#registro-'.$numeroRegistro;
                $errorCodigo = 'Código de pregunta obligatorio; usa 1-64 caracteres alfanuméricos, guión o guión bajo.';
            }

            if (! isset($grupos[$codigo])) {
                if (count($grupos) >= self::MAX_PREGUNTAS) {
                    throw new InvalidArgumentException('El archivo supera el límite de '.self::MAX_PREGUNTAS.' preguntas. Divídelo en varios archivos.');
                }
                $grupos[$codigo] = [
                    'registro' => $numeroRegistro,
                    'categoria' => $datosFila['categoria'],
                    'tema' => $datosFila['tema'],
                    'tipo' => $datosFila['tipo'],
                    'enunciado' => $datosFila['enunciado'],
                    'opciones' => [], 'errores' => [],
                ];
            } elseif ([$grupos[$codigo]['categoria'], $grupos[$codigo]['tema'], $grupos[$codigo]['tipo'], $grupos[$codigo]['enunciado']]
                !== [$datosFila['categoria'], $datosFila['tema'], $datosFila['tipo'], $datosFila['enunciado']]) {
                $grupos[$codigo]['errores'][] = 'Metadatos diferentes entre las filas de la misma pregunta (registro '.$numeroRegistro.').';
            }
            if (isset($errorCodigo)) {
                $grupos[$codigo]['errores'][] = $errorCodigo;
                unset($errorCodigo);
            }
            if (! in_array($datosFila['es_correcta'], ['0', '1'], true)) {
                $grupos[$codigo]['errores'][] = 'es_correcta debe ser 0 o 1 (registro '.$numeroRegistro.').';
            }
            if ($datosFila['texto_opcion'] === '') {
                $grupos[$codigo]['errores'][] = 'Falta texto_opcion (registro '.$numeroRegistro.').';
            }
            $grupos[$codigo]['opciones'][] = [
                'texto_opcion' => $datosFila['texto_opcion'],
                'es_correcta' => $datosFila['es_correcta'],
                'orden_correcto' => $datosFila['orden_correcto'] === '' ? null : $datosFila['orden_correcto'],
            ];
        }
        if ($grupos === []) {
            throw new InvalidArgumentException('El archivo no contiene filas de preguntas.');
        }
        return $grupos;
    }

    private function normalizarCabeceras(array $cabeceras): array
    {
        return array_map(fn ($c) => strtolower(trim((string) $c)), $cabeceras);
    }

    private function huella(array $datos): string
    {
        return hash('sha256', $datos['id_tema'].'|'.$datos['tipo'].'|'.mb_strtolower(trim($datos['enunciado'])));
    }
}
