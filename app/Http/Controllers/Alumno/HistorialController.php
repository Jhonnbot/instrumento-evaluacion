<?php

namespace App\Http\Controllers\Alumno;

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
            'dictamen' => ['nullable', Rule::in(['APROBADO', 'REPROBADO'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => array_merge(['nullable', 'date_format:Y-m-d'],
                $request->filled('desde') ? ['after_or_equal:desde'] : []),
        ]);
        $registros = $servicio->consultar($filtros, (int) $request->user()->getKey())
            ->paginate(10)->withQueryString();

        return view('alumno.historial.index', compact('registros', 'filtros'));
    }

    public function detalle(Request $request, int $intento, HistorialResultadosService $servicio): View
    {
        $registro = Intento::with(['alumno', 'examen', 'resultado'])
            ->where('id_usuario', $request->user()->getKey())
            ->whereHas('resultado')->findOrFail($intento);

        return view('historial.detalle', [
            'registro' => $registro, 'modo' => 'alumno',
            ...$servicio->detalle($registro),
        ]);
    }
}
