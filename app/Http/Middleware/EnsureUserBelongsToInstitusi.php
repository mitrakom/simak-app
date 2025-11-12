<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Institusi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToInstitusi
{
    /**
     * Handle an incoming request.
     *
     * Pastikan user yang sedang login hanya bisa mengakses
     * dashboard institusi mereka sendiri.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get institusi from route parameter
        $institusi = $request->route('institusi');

        // If institusi is string (slug), resolve it
        if (is_string($institusi)) {
            $institusi = Institusi::where('slug', $institusi)->firstOrFail();
        }

        // Get authenticated user
        $user = Auth::user();

        // Verify user belongs to this institusi
        if ($user && $user->institusi_id !== $institusi->id) {
            // User trying to access different institusi
            Auth::logout();

            return redirect()
                ->route('auth.login.form', ['institusi' => $institusi->slug])
                ->withErrors([
                    'email' => 'Anda tidak memiliki akses ke institusi ini. Silakan login dengan akun yang sesuai.',
                ]);
        }

        // Share institusi to all views
        view()->share('currentInstitusi', $institusi);

        return $next($request);
    }
}
