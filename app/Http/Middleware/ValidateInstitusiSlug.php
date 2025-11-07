<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Institusi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ValidateInstitusiSlug
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated and the route requires slug validation
        if ($request->user() && $request->route('slug')) {
            $slug = $request->route('slug');
            $user = $request->user();

            // Load user's institusi if not already loaded
            if (!$user->relationLoaded('institusi')) {
                $user->load('institusi');
            }

            // Check if user's institusi slug matches the route slug
            if (!$user->institusi || $user->institusi->slug !== $slug) {
                return response()->json([
                    'message' => 'Unauthorized. Your account is not associated with this institution.',
                    'error' => 'INVALID_INSTITUTION_SLUG'
                ], 403);
            }
        }

        return $next($request);
    }
}
