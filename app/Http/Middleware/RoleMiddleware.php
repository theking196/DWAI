<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        // Admin can do everything
        if ($userRole === 'admin') {
            return $next($request);
        }

        $allowed = match ($role) {
            'admin' => false,
            'editor' => in_array($userRole, ['editor']),
            'viewer' => true,
            default => false,
        };

        if (!$allowed) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
