<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the application remains private.
 * - Requires authentication
 * - Can optionally block external IPs (disabled by default)
 */
class EnsurePrivateAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Require authentication
        if (!$request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        // 2. Optional: Block external access (enable in routes if needed)
        // if ($this->isExternalAccess($request)) {
        //     abort(403, 'Access denied. This is a private application.');
        // }

        return $next($request);
    }

    /**
     * Check if request is from external IP.
     * Currently disabled - enable if needed.
     */
    protected function isExternalAccess(Request $request): bool
    {
        // Only allow localhost by default
        $allowedHosts = ['localhost', '127.0.0.1', '::1'];
        
        $host = $request->getHost();
        $ip = $request->ip();

        // Check if running locally
        if (in_array($host, $allowedHosts) || in_array($ip, $allowedHosts)) {
            return false;
        }

        // For future: enable external IP blocking
        // return true;
        
        return false; // Currently permissive
    }
}
