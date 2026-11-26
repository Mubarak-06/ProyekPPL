<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LarangKurir
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user()?->isKurir(), 403);

        return $next($request);
    }
}
