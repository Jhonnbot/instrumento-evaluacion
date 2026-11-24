<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Constancia;
use App\Models\Resultado;
use App\Services\Constancias\ConstanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ConstanciaController extends Controller
{
    public function index()
    {
        $resultados = Resultado::query()
            ->whereHas('intento', fn ($q) => $q->where('id_usuario', auth()->id()))
            ->with(['intento.examen'])
            ->where('dictamen', 'APROBADO')
            ->orderByDesc('fecha_calificacion')
            ->get();
        $constancias = Constancia::query()->whereIn('id_resultado', $resultados->pluck('id_resultado'))
            ->get()->keyBy('id_resultado');

        return view('alumno.constancias.index', compact('resultados', 'constancias'));
    }

    public function generar(int $resultado, ConstanciaService $servicio): RedirectResponse
    {
        $registro = Resultado::query()->with('intento')->findOrFail($resultado);
        $constancia = $servicio->emitir($registro, (int) auth()->id());

        return redirect()->route('alumno.constancias.index')->with('estado',
            $constancia->estado === 'vigente'
                ? 'Constancia disponible para descarga.'
                : 'La constancia ya fue revocada y no puede emitirse de nuevo.');
    }

    public function descargar(int $constancia, ConstanciaService $servicio): Response
    {
        $registro = Constancia::query()->with('resultado.intento')->findOrFail($constancia);
        abort_unless((int) $registro->resultado->intento->id_usuario === (int) auth()->id(), 404);
        abort_unless($registro->estado === 'vigente', 403, 'La constancia fue revocada.');
        $contenido = $servicio->pdfExistente($registro);

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$registro->folio.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
