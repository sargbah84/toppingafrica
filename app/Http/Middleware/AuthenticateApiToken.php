<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the external Blog Content API with a single static bearer token.
 *
 * The active token is the one generated from the admin Settings page (stored
 * as the `blog_api_token` setting). For backward compatibility / first boot it
 * falls back to `config('services.blog_api.token')` (BLOG_API_TOKEN env).
 * Requests must send `Authorization: Bearer <token>`. Comparison is
 * constant-time to avoid timing leaks. If no token is configured the API is
 * effectively disabled (every request 503s) rather than open.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = Setting::get('blog_api_token') ?: config('services.blog_api.token');

        if (empty($expected)) {
            return response()->json([
                'message' => 'Blog API is not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $provided = $request->bearerToken();

        if (! is_string($provided) || ! hash_equals((string) $expected, $provided)) {
            return response()->json([
                'message' => 'Invalid or missing API token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
