<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'identificador' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $identificador = trim($datos['identificador']);
        // Identificador numérico secuencial o correo electrónico.
        $campo = ctype_digit($identificador) ? 'id_usuario' : 'correo';
        $valor = $campo === 'correo' ? mb_strtolower($identificador) : $identificador;

        if (! Auth::attempt([$campo => $valor, 'password' => $datos['password'], 'estado' => 'activo'])) {
            return back()->withErrors([
                'identificador' => 'Las credenciales son incorrectas o la cuenta está inactiva.',
            ])->onlyInput('identificador');
        }

        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->tieneRol('Coordinador')
            ? route('coordinador.panel')
            : route('alumno.panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
