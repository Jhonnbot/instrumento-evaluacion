<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'correo' => mb_strtolower(trim((string) $request->input('correo'))),
            'curp' => mb_strtoupper(trim((string) $request->input('curp'))),
        ]);

        $datos = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'correo')],
            // Valida estructura CURP de 18 caracteres; no consulta RENAPO ni verifica el dígito de control.
            'curp' => ['required', 'size:18', 'regex:/^[A-Z][AEIOUX][A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/', Rule::unique('usuarios', 'curp')],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $rolAlumno = Rol::query()->where('nombre', 'Alumno')->firstOrFail();

        $usuario = User::query()->create([
            'id_rol' => $rolAlumno->getKey(), // Jamás aceptar id_rol desde registro público.
            'nombre_completo' => $datos['nombre_completo'],
            'telefono' => $datos['telefono'],
            'correo' => $datos['correo'],
            'curp' => $datos['curp'],
            'password_hash' => Hash::make($datos['password']),
            'estado' => 'activo',
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('alumno.panel')->with('mensaje', 'Registro completado. Tu ID es '.$usuario->getKey());
    }
}
