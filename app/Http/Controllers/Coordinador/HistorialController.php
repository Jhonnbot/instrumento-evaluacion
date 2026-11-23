<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Models\Intento;
use App\Services\Evaluaciones\HistorialResultadosService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public function index(Request $request, HistorialResultadosService $servicio): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'dictamen' => ['nullable', Rule::in(['APROBADO', 'REPROBADO'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => array_merge(['nullable', 'date_format:Y-m-d'],
                $request->filled('desde') ? ['after_or_equal:desde'] : []),
        ]);
        $registros = $servicio->consultar($filtros)->paginate(10)->withQueryString();

        return view('coordinador.historial.index', compact('registros', 'filtros'));
    }

    public function detalle(int $intento, HistorialResultadosService $servicio): View
    {
        $registro = Intento::with(['alumno', 'examen', 'resultado'])
            ->whereHas('resultado')->findOrFail($intento);

        return view('historial.detalle', [
            'registro' => $registro, 'modo' => 'coordinador',
            ...$servicio->detalle($registro),
        ]);
    }
}
