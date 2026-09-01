<?php

namespace App\Http\Middleware;

use App\Services\MonitoringAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!MonitoringAuth::isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Unauthorized: Admin privileges required.'], 403);
            }
            return redirect()->route('tasks.index')->with('error', 'Unauthorized: Admin privileges required.');
        }

        return $next($request);
    }
}
