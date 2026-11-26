<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PastikanKurir
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isKurir(), 403);

        return $next($request);
    }
}
