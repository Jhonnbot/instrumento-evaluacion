<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\SolicitudIntento;
use App\Services\Evaluaciones\SolicitudIntentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SolicitudIntentoController extends Controller
{
    public function index(Request $request, SolicitudIntentoService $servicio): View
    {
        return view('alumno.solicitudes.index', [
            'estado' => $servicio->estadoAlumno($request->user()),
            'solicitudes' => SolicitudIntento::with(['coordinador','intento'])
                ->where('id_usuario', $request->user()->getKey())
                ->orderByDesc('id_solicitud')->paginate(10),
        ]);
    }

    public function store(Request $request, SolicitudIntentoService $servicio): RedirectResponse
    {
        $servicio->solicitar($request->user());
        return redirect()->route('alumno.solicitudes.index')
            ->with('exito', 'Solicitud enviada. Espera la decisión del Coordinador.');
    }
}
