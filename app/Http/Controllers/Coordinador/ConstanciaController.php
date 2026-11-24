<?php

namespace App\Http\Controllers\Coordinador;

use App\Http\Controllers\Controller;
use App\Models\Constancia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConstanciaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:vigente,revocada'],
        ]);
        $consulta = Constancia::query()->with(['resultado.intento.alumno', 'resultado.intento.examen']);
        if (! empty($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }
        $buscar = trim($filtros['buscar'] ?? '');
        if ($buscar !== '') {
            $consulta->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', '%'.$buscar.'%')
                    ->orWhereHas('resultado.intento.alumno', function ($alumno) use ($buscar) {
                        $alumno->where('nombre_completo', 'like', '%'.$buscar.'%')
                            ->orWhere('correo', 'like', '%'.$buscar.'%');
                    });
            });
        }

        return view('coordinador.constancias.index', ['constancias' => $consulta->orderByDesc('id_constancia')->paginate(10)->withQueryString()]);
    }

    public function revocar(int $constancia): RedirectResponse
    {
        $registro = Constancia::query()->findOrFail($constancia);
        $registro->update(['estado' => 'revocada']);

        return redirect()->route('coordinador.constancias.index')->with('estado', 'Constancia revocada. La verificación pública indicará que ya no está vigente.');
    }
}
