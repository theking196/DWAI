<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block external/public access.
 * 
 * Enable this middleware in routes when you want to restrict
 * the app to localhost only.
 * 
 * Usage in routes:
 *   ->middleware('local.only')
 */
class BlockExternalAccess
{
    /**
     * Allowed hosts (local development).
     */
    protected array $allowedHosts = [
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $ip = $request->ip();

        // Check if local access
        if ($this->isLocalHost($host) || $this->isLocalIP($ip)) {
            return $next($request);
        }

        // Block external access
        abort(403, 'This application is private and local-only.');
    }

    protected function isLocalHost(string $host): bool
    {
        return in_array($host, $this->allowedHosts);
    }

    protected function isLocalIP(string $ip): bool
    {
        return in_array($ip, $this->allowedHosts);
    }
}
