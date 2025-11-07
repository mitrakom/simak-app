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
        // Authenticate with institusi_id constraint
        $request->authenticate($institusi->id);

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Get the authenticated user
        $user = Auth::user();

        // Verify user belongs to this institusi (double check)
        if ($user->institusi_id !== $institusi->id) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'User tidak terdaftar pada institusi ini.',
            ]);
        }

        // Create Sanctum token for API access
        $token = $user->createToken('web-session')->plainTextToken;

        // Store token in session for future API calls
        session(['api_token' => $token]);

        // Log successful login
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'ip_address' => $request->ip(),
        ]);

        // Flash success message
        session()->flash('success', 'Login berhasil! Selamat datang, ' . $user->name);

        // Redirect to admin dashboard
        return redirect()->route('admin.dashboard', ['institusi' => $institusi->slug]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request, Institusi $institusi): RedirectResponse
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

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
