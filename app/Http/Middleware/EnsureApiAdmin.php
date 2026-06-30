<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) $request->user()?->isAdmin(), 403, 'Hanya admin.');

        return $next($request);
    }
}
