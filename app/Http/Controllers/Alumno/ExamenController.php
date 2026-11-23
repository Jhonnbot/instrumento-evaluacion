<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Services\Evaluaciones\ExamenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamenController extends Controller
{
    public function index(Request $request, ExamenService $servicio): View
    {
        $examen = $servicio->examen();
        return view('alumno.examen.index', ['examen'=>$examen,
            'estado'=>$examen ? $servicio->estado($request->user(), $examen) : null,
            'porCategoria'=>(int)config('examen_cocosoft.preguntas_por_categoria', 2)]);
    }

    public function iniciar(Request $request, ExamenService $servicio): RedirectResponse
    {
        $examen = $servicio->examen();
        if (!$examen) return redirect()->route('alumno.examen.index')->with('aviso', 'No hay un examen formal habilitado.');
        $intento = $servicio->iniciar($request->user(), $examen);
        return redirect()->route('alumno.examen.preguntas', [$intento->getKey(), 1]);
    }

    public function preguntas(Request $request, int $intento, int $pagina, ExamenService $servicio): View|RedirectResponse
    {
        $actual = $servicio->verificarPropiedad($request->user(), $intento);
        if ($actual->estado !== 'en_curso') return redirect()->route('alumno.examen.resultado', $intento);
        $paginas = $servicio->paginas($actual);
        abort_unless($pagina >= 1 && $pagina <= $paginas, 404);
        return view('alumno.examen.preguntas', ['intento'=>$actual,
            'preguntas'=>$servicio->bloque($actual, $pagina), 'pagina'=>$pagina, 'paginas'=>$paginas,
            'expira'=>$servicio->expira($actual)->toIso8601String()]);
    }

    public function guardar(Request $request, int $intento, int $pagina, ExamenService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'accion'=>['required','in:anterior,siguiente,finalizar'],
            'respuestas'=>['sometimes','array'],
        ]);
        $concluido = $servicio->guardar($request->user(), $intento, $pagina,
            $datos['respuestas'] ?? [], $datos['accion']==='finalizar');
        if ($concluido) return redirect()->route('alumno.examen.resultado', $intento);
        $actual = $servicio->verificarPropiedad($request->user(), $intento);
        $paginas = $servicio->paginas($actual);
        $destino = $datos['accion'] === 'anterior' ? max(1, $pagina-1) : min($paginas, $pagina+1);
        return redirect()->route('alumno.examen.preguntas', [$intento, $destino]);
    }

    /** Autosalvado de respuestas en cada cambio, antes del vencimiento. */
    public function autoguardar(Request $request, int $intento, int $pagina, ExamenService $servicio): JsonResponse
    {
        $datos = $request->validate(['respuestas'=>['sometimes','array']]);
        $concluido = $servicio->guardar($request->user(), $intento, $pagina, $datos['respuestas'] ?? []);
        return response()->json(['concluido'=>$concluido]);
    }

    public function finalizar(Request $request, int $intento, ExamenService $servicio): RedirectResponse
    {
        $actual = $servicio->verificarPropiedad($request->user(), $intento);
        $servicio->finalizar($actual->getKey());
        return redirect()->route('alumno.examen.resultado', $intento);
    }

    public function resultado(Request $request, int $intento, ExamenService $servicio): View|RedirectResponse
    {
        $actual = $servicio->verificarPropiedad($request->user(), $intento);
        if ($actual->estado === 'en_curso') return redirect()->route('alumno.examen.preguntas', [$intento, 1]);
        $resultado = $actual->resultado;
        abort_unless($resultado, 409);
        $detalle = \Illuminate\Support\Facades\DB::table('detalle_resultado_categoria as d')
            ->join('categorias as c','c.id_categoria','=','d.id_categoria')
            ->where('d.id_resultado', $resultado->getKey())->orderBy('c.id_categoria')
            ->get(['c.nombre','d.puntaje_obtenido']);
        return view('alumno.examen.resultado', compact('actual', 'resultado', 'detalle'));
    }
}
