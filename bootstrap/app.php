<?php

use App\Http\Middleware\LogRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            LogRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tampered/malformed Livewire payloads (e.g. bots posting an array where
        // a typed string property is expected) surface as TypeError during
        // hydration. Treat these as malformed requests (422) instead of 500s.
        $exceptions->render(function (\TypeError $e, \Illuminate\Http\Request $request) {
            if (! $request->is('livewire/update')) {
                return null;
            }
            if (! preg_match('/Cannot assign \S+ to property .* of type/', $e->getMessage())) {
                return null;
            }
            $request->attributes->set('_exception', $e);

            return response()->json(['message' => 'Malformed request.'], 422);
        });

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e) {
            // Attach exception to response so LogRequests middleware can capture it
            if ($response instanceof \Illuminate\Http\Response) {
                $response->exception = $e;
            }

            return $response;
        });
    })->create();
