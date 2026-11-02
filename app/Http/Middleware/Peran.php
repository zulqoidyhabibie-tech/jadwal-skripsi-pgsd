<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Peran
{
    public function handle(Request $request, Closure $next, string ...$peran)
    {
        $user = $request->user();
        // Admin boleh mengakses semua halaman.
        if (! $user || (! $user->hasRole('admin') && ! in_array($user->role, $peran, true))) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
