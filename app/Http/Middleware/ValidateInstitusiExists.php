<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Institusi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInstitusiExists
{
    /**
     * Handle an incoming request.
     *
     * Middleware ini memastikan bahwa slug institusi yang diakses:
     * 1. Benar-benar terdaftar di database
     * 2. Tidak menggunakan slug default atau slug yang tidak valid
     * 3. Redirect ke 404 jika slug tidak valid
     */
    public function handle(Request $request, Closure $next): Response
    {
        $institusi = $request->route('institusi');

        // Jika parameter institusi tidak ada di route, skip validation
        if (! $institusi) {
            return $next($request);
        }

        // Jika institusi adalah string (bukan model), coba cari di database
        if (is_string($institusi)) {
            $institusiModel = Institusi::where('slug', $institusi)->first();

            if (! $institusiModel) {
                abort(404, 'Institusi tidak ditemukan.');
            }

            // Replace route parameter dengan model
            $request->route()->setParameter('institusi', $institusiModel);
        }

        // Jika institusi adalah model, pastikan slug tidak menggunakan nilai terlarang
        if ($institusi instanceof Institusi) {
            $forbiddenSlugs = [
                'default',
                'admin',
                'api',
                'auth',
                'login',
                'register',
                'logout',
                'test',
                'testing',
                'staging',
                'production',
                'dev',
                'development',
                'user',
                'users',
                'dashboard',
                'home',
                'about',
                'contact',
                'terms',
                'privacy',
                'help',
                'support',
                'docs',
                'documentation',
            ];

            if (in_array(strtolower($institusi->slug), $forbiddenSlugs)) {
                abort(404, 'Slug institusi tidak valid.');
            }
        }

        // Jika bukan string dan bukan model Institusi, abort
        if (! $institusi instanceof Institusi && ! is_string($institusi)) {
            abort(404, 'Institusi tidak valid.');
        }

        return $next($request);
    }
}
