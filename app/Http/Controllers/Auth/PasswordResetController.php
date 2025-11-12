<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\SendResetLinkRequest;
use App\Models\Institusi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Display the password reset request form.
     */
    public function showRequestForm(Institusi $institusi): View
    {
        return view('auth.password.request', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLink(SendResetLinkRequest $request, Institusi $institusi): RedirectResponse
    {
        // Verify user belongs to this institusi
        $user = User::where('email', $request->email)
            ->where('institusi_id', $institusi->id)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'Email tidak terdaftar pada institusi ini.',
            ])->withInput();
        }

        // Delete old tokens for this email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Create new token
        $token = Str::random(64);

        // Store token in database
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // TODO: Send email with reset link
        // For now, we'll flash the token (in production, send via email)

        // Generate reset URL
        $resetUrl = route('auth.password.reset.form', [
            'institusi' => $institusi->slug,
            'token' => $token,
        ]).'?email='.urlencode($request->email);

        // Flash success message with URL (in production, this would be sent via email)
        session()->flash('reset_link', $resetUrl);
        session()->flash('success', 'Link reset password telah dibuat. Silakan gunakan link berikut untuk reset password Anda.');

        return back();
    }

    /**
     * Display the password reset form.
     */
    public function showResetForm(Institusi $institusi, string $token): View
    {
        return view('auth.password.reset', [
            'institusi' => $institusi,
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(PasswordResetRequest $request, Institusi $institusi): RedirectResponse
    {
        // Verify token exists and is valid
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $resetRecord) {
            return back()->withErrors([
                'email' => 'Token reset password tidak valid atau sudah kadaluarsa.',
            ])->withInput();
        }

        // Verify token matches
        if (! Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors([
                'email' => 'Token reset password tidak valid.',
            ])->withInput();
        }

        // Check if token is expired (24 hours)
        $tokenAge = now()->diffInHours($resetRecord->created_at);
        if ($tokenAge > 24) {
            // Delete expired token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return back()->withErrors([
                'email' => 'Token reset password sudah kadaluarsa. Silakan request ulang.',
            ])->withInput();
        }

        // Find user and verify they belong to this institusi
        $user = User::where('email', $request->email)
            ->where('institusi_id', $institusi->id)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'email' => 'User tidak ditemukan pada institusi ini.',
            ])->withInput();
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Revoke all existing tokens
        $user->tokens()->delete();

        // Delete the reset token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Flash success message
        session()->flash('success', 'Password berhasil direset! Silakan login dengan password baru Anda.');

        // Redirect to login page
        return redirect()->route('auth.login.form', ['institusi' => $institusi->slug]);
    }
}
