<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFeederClientReady
{
    /**
     * Middleware untuk memastikan user terotentikasi dan memiliki konfigurasi feeder yang lengkap
     *
     * Middleware ini akan:
     * 1. Memastikan user sudah terotentikasi
     * 2. Memastikan user memiliki institusi
     * 3. Memastikan institusi memiliki konfigurasi feeder yang lengkap
     */
    public function handle(Request $request, Closure $next)
    {
        // Cek user terotentikasi
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Authentication required',
                'error' => 'User must be authenticated to access this resource',
            ], 401);
        }

        // Load institusi relation jika belum ter-load
        if (! $user->relationLoaded('institusi')) {
            $user->load('institusi');
        }

        // Cek user memiliki institusi
        if (! $user->institusi) {
            return response()->json([
                'message' => 'Institusi not found',
                'error' => 'Authenticated user must have an associated institusi',
            ], 422);
        }

        // Cek institusi memiliki konfigurasi feeder lengkap
        $institusi = $user->institusi;
        if (
            empty($institusi->feeder_url) ||
            empty($institusi->feeder_username) ||
            empty($institusi->feeder_password)
        ) {
            return response()->json([
                'message' => 'Feeder configuration incomplete',
                'error' => 'User\'s institusi must have complete feeder configuration (feeder_url, feeder_username, feeder_password)',
                'institusi' => [
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                    'has_feeder_url' => ! empty($institusi->feeder_url),
                    'has_feeder_username' => ! empty($institusi->feeder_username),
                    'has_feeder_password' => ! empty($institusi->feeder_password),
                ],
            ], 422);
        }

        // Simpan institusi di request untuk digunakan di controller/service
        $request->attributes->set('institusi', $institusi);

        return $next($request);
    }
}
