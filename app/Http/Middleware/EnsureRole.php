<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas akses berdasarkan peran (FR-01, US-1, US-6, US-7).
 * Pemakaian: Route::middleware('role:admin') atau 'role:dosen,admin'.
 * Akses ditolak dengan HTTP 403 sesuai acceptance criteria.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 401);

        if (! $user->hasRole(...$roles)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        return $next($request);
    }
}
