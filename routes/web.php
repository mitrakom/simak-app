<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Multi-Tenant Routes (berdasarkan slug institusi)
Route::prefix('{institusi:slug}')->middleware('validate.institusi.exists')->group(function () {

    // Landing Page
    Route::get('/', [LandingPageController::class, 'show'])->name('landing');

    // Authentication Routes
    Route::prefix('auth')->name('auth.')->group(function () {
        // Login
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
        Route::post('/login', [AuthController::class, 'login'])->name('login');

        // Logout
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

        // Password Reset
        Route::prefix('password')->name('password.')->group(function () {
            Route::get('/request', [PasswordResetController::class, 'showRequestForm'])->name('request');
            Route::post('/send-link', [PasswordResetController::class, 'sendResetLink'])->name('send-link');
            Route::get('/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('reset.form');
            Route::post('/reset/{token}', [PasswordResetController::class, 'reset'])->name('reset');
        });
    });

    // Admin Routes (Protected)
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'belongs.to.institusi'])->group(function () {
        Volt::route('/', 'admin.dashboard')->name('dashboard');
        Volt::route('/users', 'admin.users')->name('users');
        Volt::route('/analytics', 'admin.analytics')->name('analytics');
        Route::get('/synchronize', \App\Livewire\Admin\Synchronize::class)->name('synchronize');
        Volt::route('/settings', 'admin.settings.index')->name('settings');
        Volt::route('/settings/theme', 'admin.settings.theme')->name('settings.theme');
    });
});
