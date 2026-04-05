<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (int) round((microtime(true) - $start) * 1000);
        $statusCode = $response->getStatusCode();

        // Log all errors (4xx/5xx) and sample successful requests (1 in 10)
        $shouldLog = $statusCode >= 400 || random_int(1, 10) === 1;

        if ($shouldLog) {
            $responseBody = null;
            $exception = null;

            if ($statusCode >= 400) {
                // Capture response body (truncated)
                $content = $response->getContent();
                if ($content) {
                    $responseBody = substr($content, 0, 5000);
                }

                // Capture exception from Laravel's exception handler
                $lastException = app('request')->attributes->get('_exception')
                    ?? ($response->exception ?? null);
                if ($lastException instanceof \Throwable) {
                    $exception = get_class($lastException) . ': ' . $lastException->getMessage()
                        . "\n\n" . $lastException->getFile() . ':' . $lastException->getLine()
                        . "\n\n" . substr($lastException->getTraceAsString(), 0, 3000);
                }
            }

            $userId = auth()->id();
            $method = $request->method();
            $url = substr($request->fullUrl(), 0, 2000);
            $ip = $request->ip();
            $ua = $request->userAgent() ? substr($request->userAgent(), 0, 500) : null;
            $routeName = $request->route()?->getName();
            $errorMessage = $statusCode >= 400 ? $this->extractErrorMessage($statusCode, $request) : null;

            app()->terminating(function () use ($userId, $method, $url, $statusCode, $ip, $ua, $duration, $errorMessage, $exception, $responseBody, $routeName) {
                try {
                    RequestLog::create([
                        'user_id' => $userId,
                        'method' => $method,
                        'url' => $url,
                        'status_code' => $statusCode,
                        'ip_address' => $ip,
                        'user_agent' => $ua,
                        'response_time_ms' => $duration,
                        'error_message' => $errorMessage,
                        'exception' => $exception,
                        'response_body' => $responseBody,
                        'route_name' => $routeName,
                        'created_at' => now(),
                    ]);
                } catch (\Throwable) {
                    // Silently fail
                }
            });
        }

        return $response;
    }

    private function extractErrorMessage(int $statusCode, Request $request): ?string
    {
        $messages = [
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => "Not Found: {$request->path()}",
            405 => "Method Not Allowed: {$request->method()}",
            419 => 'Page Expired (CSRF)',
            422 => 'Validation Error',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];

        return $messages[$statusCode] ?? "HTTP {$statusCode}";
    }
}
