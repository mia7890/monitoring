<?php

namespace App\Http\Middleware;

use App\Services\MonitoringAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMonitoringAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!MonitoringAuth::role()) {
            return redirect()->route('access');
        }

        return $next($request);
    }
}
