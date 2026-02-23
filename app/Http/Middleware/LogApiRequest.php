<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Logs every API request and response.
 *
 * This middleware intercepts all /api/v1/ requests, recording:
 * - HTTP method (GET, POST, PATCH, etc.)
 * - Full URL
 * - Client IP address
 * - Timestamp
 * - Response status code
 *
 * Logs are written to storage/logs/api_requests.log via the
 * 'api_requests' channel defined in config/logging.php.
 *
 * This demonstrates the middleware pipeline pattern — a cross-cutting
 * concern (logging) applied to all routes without modifying controllers.
 */
class LogApiRequest
{
    /**
     * Handle an incoming request.
     *
     * Middleware: The request passes through this method BEFORE reaching
     * the controller. After the controller returns a response, we log
     * both the request details and the response status code.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Let the request pass through to the controller and get the response
        $response = $next($request);

        // Middleware: Log after the response is generated so we capture the status code
        $logEntry = sprintf(
            "[%s] %s %s | IP: %s | Status: %d",
            now()->toDateTimeString(),
            $request->method(),
            $request->fullUrl(),
            $request->ip(),
            $response->getStatusCode(),
        );

        Log::channel('api_requests')->info($logEntry);

        return $response;
    }
}
