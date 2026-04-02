<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsStaff
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has staff or super-admin privileges.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->is_staff && ! $user->is_super_admin)) {
            abort(403, 'Unauthorized. Staff access required.');
        }

        return $next($request);
    }
}
