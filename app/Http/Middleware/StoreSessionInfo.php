<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StoreSessionInfo
{
    public function handle(Request $request, Closure $next)
    {
        // Stocker IP et user-agent dans la session
        if (!$request->session()->has('_ip')) {
            $request->session()->put('_ip', $request->ip());
        }

        if (!$request->session()->has('_user_agent')) {
            $request->session()->put('_user_agent', $request->userAgent());
        }

        return $next($request);
    }
}
