<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Models\SolicitudIntento;
use App\Services\Evaluaciones\SolicitudIntentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SolicitudIntentoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'estado' => ['nullable', Rule::in(['pendiente', 'aprobada', 'rechazada'])],
        ]);
        $estado = $filtros['estado'] ?? '';
        $solicitudes = SolicitudIntento::with(['alumno', 'coordinador', 'intento'])
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->orderByRaw("CASE WHEN estado = 'pendiente' THEN 0 ELSE 1 END")
            ->orderByDesc('id_solicitud')->paginate(10)->withQueryString();

        return view('coordinador.solicitudes.index', compact('solicitudes', 'estado'));
    }

    public function resolver(Request $request, int $solicitud, SolicitudIntentoService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'decision' => ['required', Rule::in(['aprobar', 'rechazar'])],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        $resuelta = $servicio->resolver($request->user(), $solicitud,
            $datos['decision'], $datos['observaciones'] ?? null);

        return redirect()->route('coordinador.solicitudes.index')
            ->with('exito', $resuelta->estado === 'aprobada'
                ? 'Solicitud aprobada: se habilitó exactamente un intento adicional.'
                : 'Solicitud rechazada. El alumno podrá consultar el estado.');
    }
}
