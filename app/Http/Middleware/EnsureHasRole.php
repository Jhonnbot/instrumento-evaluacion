<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasRole
{
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        abort_unless(Auth::user()?->tieneRol($rol), 403);

        return $next($request);
    }
}
