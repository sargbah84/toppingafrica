<?php

use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\LogRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            LogRequests::class,
        ]);

        // Stateless API: no session/CSRF. Token auth is applied per-route
        // in routes/api.php via the `api.token` middleware alias.
        $middleware->api(append: [
            SecurityHeaders::class,
            LogRequests::class,
        ]);

        $middleware->alias([
            'api.token' => AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tampered/malformed Livewire payloads (e.g. bots posting an array where
        // a typed string property is expected) surface as TypeError during
        // hydration. Treat these as malformed requests (422) instead of 500s.
        $exceptions->render(function (TypeError $e, Request $request) {
            if (! $request->is('livewire/update')) {
                return null;
            }
            if (! preg_match('/Cannot assign \S+ to property .* of type/', $e->getMessage())) {
                return null;
            }
            $request->attributes->set('_exception', $e);

            return response()->json(['message' => 'Malformed request.'], 422);
        });

        $exceptions->respond(function (Symfony\Component\HttpFoundation\Response $response, Throwable $e) {
            // Attach exception to response so LogRequests middleware can capture it
            if ($response instanceof Response) {
                $response->exception = $e;
            }

            return $response;
        });
    })->create();
