<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        // Nueva consulta: el estado pudo cambiar mientras existía una sesión activa.
        $usuarioActual = $usuario?->fresh();

        if ($usuario && ! $usuarioActual?->estaActivo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'identificador' => 'Tu cuenta está desactivada. Contacta al coordinador.',
            ]);
        }

        if ($usuarioActual) {
            // Refresca también el rol, no solo el estado de la cuenta.
            Auth::setUser($usuarioActual);
        }

        return $next($request);
    }
}
