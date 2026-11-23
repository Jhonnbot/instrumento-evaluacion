<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Tema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TemaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate(['categoria' => ['nullable', 'integer', Rule::exists('categorias', 'id_categoria')]]);
        $categoria = $filtros['categoria'] ?? null;

        return view('coordinador.banco.temas', [
            'categorias' => Categoria::orderBy('nombre')->get(),
            'categoriaFiltro' => $categoria,
            'temas' => Tema::with('categoria')->withCount('preguntas')
                ->when($categoria, fn ($q) => $q->where('id_categoria', $categoria))
                ->orderBy('id_categoria')->orderBy('nombre')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $this->validarCategoriaActiva((int) $datos['id_categoria']);
        Tema::create($datos);

        return redirect()->route('coordinador.banco.temas.index')->with('mensaje', 'Tema registrado.');
    }

    public function update(Request $request, Tema $tema): RedirectResponse
    {
        $datos = $this->validar($request, $tema);
        if ((int) $datos['id_categoria'] !== (int) $tema->id_categoria) {
            if ($tema->preguntas()->exists()) {
                throw ValidationException::withMessages([
                    'id_categoria' => 'No se puede mover un tema que contiene preguntas.',
                ]);
            }
            $this->validarCategoriaActiva((int) $datos['id_categoria']);
        }
        $tema->update($datos);

        return back()->with('mensaje', 'Tema actualizado.');
    }

    private function validar(Request $request, ?Tema $tema = null): array
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'descripcion' => $request->filled('descripcion') ? trim((string) $request->input('descripcion')) : null,
        ]);
        $categoria = $request->input('id_categoria');

        return $request->validate([
            'id_categoria' => ['required', 'integer', Rule::exists('categorias', 'id_categoria')],
            'nombre' => [
                'required', 'string', 'max:150',
                Rule::unique('temas', 'nombre')->where('id_categoria', $categoria)
                    ->ignore($tema?->getKey(), 'id_tema'),
            ],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function validarCategoriaActiva(int $id): void
    {
        if (! Categoria::whereKey($id)->where('estado', true)->exists()) {
            throw ValidationException::withMessages([
                'id_categoria' => 'La categoría seleccionada debe estar activa.',
            ]);
        }
    }
}
