<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Batasi endpoint API ke role tertentu, mis. `api.role:marshal,admin`. */
class EnsureApiRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(in_array($request->user()?->role, $roles, true), 403, 'Akses ditolak untuk role ini.');

        return $next($request);
    }
}
