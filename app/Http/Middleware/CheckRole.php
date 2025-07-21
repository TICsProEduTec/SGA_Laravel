<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle($request, Closure $next, $role)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        // Admin validación por email o rol
        if ($role === 'admin' && $user->email !== 'admin@colegiopceirafaelgaleth.com') {
            abort(403, 'No eres administrador.');
        }

        // Profesor validación por rol
        if ($role === 'profesor' && $user->rol !== 'profesor') {
            abort(403, 'No eres profesor.');
        }

        return $next($request);
    }
}
