<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Membatasi akses berdasarkan role. Dipakai langsung dengan nama kelas dan parameter,
 * tanpa alias di bootstrap/app.php:   CekRole::class . ':admin,frontdesk'
 */
class CekRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! $user->punyaRole(...$roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
