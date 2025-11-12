<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Institusi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login form.
     */
    public function showLoginForm(Institusi $institusi): View
    {
        return view('auth.login', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(LoginRequest $request, Institusi $institusi): RedirectResponse
    {
        Log::info('Login attempt started', [
            'email' => $request->input('email'),
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
        ]);

        // Authenticate with institusi_id constraint
        $request->authenticate($institusi->id);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Get the authenticated user
        $user = Auth::user();

        Log::info('User authenticated', [
            'user_id' => $user->id,
            'is_authenticated' => Auth::check(),
        ]);

        // Verify user belongs to this institusi (double check)
        if ($user->institusi_id !== $institusi->id) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'User tidak terdaftar pada institusi ini.',
            ]);
        }

        // Log successful login
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'ip_address' => $request->ip(),
        ]);

        // Flash success message
        session()->flash('success', 'Login berhasil! Selamat datang, '.$user->name);

        $redirectUrl = route('admin.dashboard', ['institusi' => $institusi->slug]);
        Log::info('Redirecting to dashboard', ['url' => $redirectUrl]);

        // Redirect to admin dashboard
        return redirect()->route('admin.dashboard', ['institusi' => $institusi->slug]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request, Institusi $institusi): RedirectResponse
    {
        // Logout from web session
        Auth::guard('web')->logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Flash message
        session()->flash('success', 'Logout berhasil.');

        // Redirect to login page
        return redirect()->route('auth.login.form', ['institusi' => $institusi->slug]);
    }
}
