<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Services\Simulador\PracticaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimuladorController extends Controller
{
    public function index(Request $request, PracticaService $servicio): View
    {
        $ventana = $servicio->ventana($request->user());
        if (! $ventana['disponible']) {
            $request->session()->forget(PracticaService::SESION);
        }

        return view('alumno.simulador.index', ['ventana' => $ventana, 'cantidad' => $servicio->cantidad(),
            'enCurso' => (bool) $this->actual($request)]);
    }

    public function iniciar(Request $request, PracticaService $servicio): RedirectResponse
    {
        if (! $servicio->ventana($request->user())['disponible']) {
            return redirect()->route('alumno.simulador.index')->with('aviso', 'Tu periodo de práctica no está disponible.');
        }
        $sesion = $servicio->iniciar($request->user());
        if (! $sesion) {
            return redirect()->route('alumno.simulador.index')->with('aviso',
                'No hay suficientes preguntas activas. Se necesitan '.$servicio->cantidad().' preguntas con opciones.');
        }
        $request->session()->put(PracticaService::SESION, $sesion);

        return redirect()->route('alumno.simulador.preguntas', ['pagina' => 1]);
    }

    public function preguntas(Request $request, int $pagina, PracticaService $servicio): View|RedirectResponse
    {
        $sesion = $this->vigente($request, $servicio);
        if (! $sesion) {
            return redirect()->route('alumno.simulador.index');
        }
        $paginas = (int) ceil(count($sesion['ids']) / 2);
        abort_unless($pagina >= 1 && $pagina <= $paginas, 404);

        return view('alumno.simulador.preguntas', [
            'preguntas' => $servicio->bloque($sesion, $pagina), 'respuestas' => $sesion['respuestas'],
            'token' => $sesion['token'], 'pagina' => $pagina, 'paginas' => $paginas,
            'total' => count($sesion['ids']),
        ]);
    }

    public function guardar(Request $request, int $pagina, PracticaService $servicio): RedirectResponse
    {
        $sesion = $this->vigente($request, $servicio);
        if (! $sesion) {
            return redirect()->route('alumno.simulador.index');
        }
        $paginas = (int) ceil(count($sesion['ids']) / 2);
        abort_unless($pagina >= 1 && $pagina <= $paginas, 404);
        $datos = $request->validate([
            'accion' => ['required', 'in:anterior,siguiente,finalizar'],
            'token' => ['required', 'uuid'], 'respuestas' => ['sometimes', 'array'],
        ]);
        abort_unless(hash_equals($sesion['token'], $datos['token']), 409,
            'Esta práctica fue reemplazada por otra.');
        $servicio->guardar($sesion, $servicio->bloque($sesion, $pagina), $datos['respuestas'] ?? []);
        $request->session()->put(PracticaService::SESION, $sesion);
        if ($datos['accion'] === 'finalizar') {
            $resultado = $servicio->calificar($sesion);
            $request->session()->forget(PracticaService::SESION);
            $request->session()->flash(PracticaService::RESULTADO, $resultado);

            return redirect()->route('alumno.simulador.resultado');
        }
        $destino = $datos['accion'] === 'anterior' ? max(1, $pagina - 1) : min($paginas, $pagina + 1);

        return redirect()->route('alumno.simulador.preguntas', ['pagina' => $destino]);
    }

    public function resultado(Request $request): View|RedirectResponse
    {
        $resultado = $request->session()->get(PracticaService::RESULTADO);
        if (! is_array($resultado)) {
            return redirect()->route('alumno.simulador.index')->with('aviso',
                'El resultado temporal ya no está disponible. No se guarda historial.');
        }

        return view('alumno.simulador.resultado', compact('resultado'));
    }

    private function actual(Request $request): ?array
    {
        $sesion = $request->session()->get(PracticaService::SESION);
        if (! is_array($sesion) || (int) ($sesion['usuario'] ?? 0) !== (int) $request->user()->getKey()) {
            $request->session()->forget(PracticaService::SESION);

            return null;
        }

        return $sesion;
    }

    private function vigente(Request $request, PracticaService $servicio): ?array
    {
        if (! $servicio->ventana($request->user())['disponible']) {
            $request->session()->forget(PracticaService::SESION);

            return null;
        }

        return $this->actual($request);
    }
}
