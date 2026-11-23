<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinador\GuardarPreguntaRequest;
use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Tema;
use App\Services\BancoPreguntas\GuardarPreguntaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PreguntaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'categoria' => ['nullable', 'integer', Rule::exists('categorias', 'id_categoria')],
            'tema' => ['nullable', 'integer', Rule::exists('temas', 'id_tema')],
            'tipo' => ['nullable', Rule::in(array_keys(Pregunta::TIPOS))],
            'estado' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $preguntas = Pregunta::with('tema.categoria')->withCount(['opciones', 'preguntasIntento'])
            ->when($filtros['categoria'] ?? null, fn ($q, $id) => $q->whereHas('tema', fn ($sub) => $sub->where('id_categoria', $id)))
            ->when($filtros['tema'] ?? null, fn ($q, $id) => $q->where('id_tema', $id))
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->when(isset($filtros['estado']) && $filtros['estado'] !== '', fn ($q) => $q->where('estado', $filtros['estado']))
            ->orderByDesc('id_pregunta')->paginate(12)->withQueryString();

        return view('coordinador.banco.preguntas', [
            'preguntas' => $preguntas,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'temas' => Tema::orderBy('nombre')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return $this->formulario(null);
    }

    public function edit(Pregunta $pregunta): View
    {
        abort_if($pregunta->preguntasIntento()->exists(), 409, 'Este reactivo ya fue seleccionado para un examen; no puede editarse.');

        return $this->formulario($pregunta->load('opciones'));
    }

    public function store(GuardarPreguntaRequest $request, GuardarPreguntaService $servicio): RedirectResponse
    {
        $servicio->crear($request->validated());

        return redirect()->route('coordinador.banco.index')->with('mensaje', 'Pregunta registrada con sus opciones.');
    }

    public function update(GuardarPreguntaRequest $request, Pregunta $pregunta, GuardarPreguntaService $servicio): RedirectResponse
    {
        $servicio->actualizar($pregunta, $request->validated());

        return redirect()->route('coordinador.banco.index')->with('mensaje', 'Pregunta y opciones actualizadas.');
    }

    public function estado(Request $request, Pregunta $pregunta): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['0', '1'])]]);
        $pregunta->update(['estado' => $datos['estado'] === '1']);

        return back()->with('mensaje', 'Estado del reactivo actualizado.');
    }

    private function formulario(?Pregunta $pregunta): View
    {
        return view('coordinador.banco.formulario-pregunta', [
            'pregunta' => $pregunta,
            'temas' => Tema::with('categoria')->whereHas('categoria', fn ($q) => $q->where('estado', true))
                ->orderBy('id_categoria')->orderBy('nombre')->get(),
        ]);
    }
}
