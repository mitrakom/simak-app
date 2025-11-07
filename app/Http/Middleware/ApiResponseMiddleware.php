<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set proper headers for API responses
        $response = $next($request);

        if ($request->is('api/*')) {
            // Ensure JSON response for API routes
            if (!$response instanceof JsonResponse && $response->getStatusCode() >= 400) {
                return response()->json([
                    'message' => 'An error occurred',
                    'status' => $response->getStatusCode(),
                ], $response->getStatusCode());
            }

            // Add common headers
            if ($response instanceof JsonResponse) {
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set('Cache-Control', 'no-cache, private');
            }
        }

        return $response;
    }
}
