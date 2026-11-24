<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('coordinador.banco.categorias', [
            'categorias' => Categoria::withCount('temas')->orderBy('id_categoria')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('categorias', 'nombre')],
        ]);

        DB::transaction(function () use ($datos): void {
            // Serializa las altas incluso cuando la tabla categorias está vacía (MySQL).
            DB::table('roles')->where('nombre', 'Coordinador')->lockForUpdate()->first();
            if (Categoria::count() >= 5) {
                throw ValidationException::withMessages([
                    'nombre' => 'El banco admite exactamente cinco categorías; no se pueden crear más.',
                ]);
            }
            Categoria::create(['nombre' => $datos['nombre'], 'estado' => true]);
        });

        return back()->with('mensaje', 'Categoría creada con ponderación fija del 20 %.');
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $request->merge(['nombre' => trim((string) $request->input('nombre'))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('categorias', 'nombre')->ignore($categoria->getKey(), 'id_categoria')],
        ]);

        $categoria->update(['nombre' => $datos['nombre']]);

        return back()->with('mensaje', 'Nombre de la categoría actualizado.');
    }

    public function estado(Request $request, Categoria $categoria): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['0', '1'])]]);
        $categoria->update(['estado' => $datos['estado'] === '1']);

        return back()->with('mensaje', 'Estado de la categoría actualizado.');
    }
}
