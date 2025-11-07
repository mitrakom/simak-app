<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memastikan user memiliki institusi
 * Ini adalah requirement dasar untuk multi-tenancy
 */
class EnsureUserHasInstitusi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Pastikan user sudah terautentikasi
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'User must be authenticated'
            ], 401);
        }

        // Pastikan user memiliki institusi_id
        if (!$user->institusi_id) {
            return response()->json([
                'message' => 'Forbidden - No Institusi',
                'error' => 'User must be associated with an institusi'
            ], 403);
        }

        // Load relasi institusi jika belum dimuat
        if (!$user->relationLoaded('institusi')) {
            $user->load('institusi');
        }

        // Pastikan institusi exists
        if (!$user->institusi) {
            return response()->json([
                'message' => 'Forbidden - Institusi Not Found',
                'error' => 'User institusi not found in database'
            ], 403);
        }

        return $next($request);
    }
}
