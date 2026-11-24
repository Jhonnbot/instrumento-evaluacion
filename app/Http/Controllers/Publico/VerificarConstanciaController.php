<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Constancia;
use Illuminate\Http\Request;

class VerificarConstanciaController extends Controller
{
    public function index()
    {
        return view('constancias.verificar', ['constancia' => null, 'consultado' => false]);
    }

    public function consultar(Request $request)
    {
        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:64'],
            'clave' => ['required', 'string', 'max:128'],
        ]);
        $constancia = Constancia::query()->with(['resultado.intento.alumno', 'resultado.intento.examen'])
            ->where('folio', trim($datos['folio']))
            ->where('clave_verificacion', trim($datos['clave']))->first();

        return view('constancias.verificar', compact('constancia') + ['consultado' => true]);
    }

    public function porQr(string $clave)
    {
        $constancia = Constancia::query()->with(['resultado.intento.alumno', 'resultado.intento.examen'])
            ->where('clave_verificacion', $clave)->first();

        return view('constancias.verificar', compact('constancia') + ['consultado' => true]);
    }
}
