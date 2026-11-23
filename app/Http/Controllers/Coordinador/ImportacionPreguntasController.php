<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Services\BancoPreguntas\ImportarPreguntasCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ImportacionPreguntasController extends Controller
{
    public function index(): View
    {
        return view('coordinador.banco.importacion');
    }

    public function plantilla(): BinaryFileResponse
    {
        $ruta = base_path('resources/plantillas/cocosoft_preguntas_plantilla.csv');
        abort_unless(is_file($ruta), 404);

        return response()->download($ruta, 'cocosoft_preguntas_plantilla.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importar(Request $request, ImportarPreguntasCsvService $servicio): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:2048', 'extensions:csv,txt'],
        ], [
            'archivo.extensions' => 'Guarda el archivo desde Excel como CSV UTF-8 (no XLSX).',
            'archivo.max' => 'El CSV no debe superar los 2 MB.',
        ]);

        try {
            $reporte = $servicio->importar($request->file('archivo')->getRealPath());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['archivo' => $e->getMessage()]);
        }

        return redirect()->route('coordinador.banco.importacion.index')->with('reporte_importacion', $reporte);
    }
}
