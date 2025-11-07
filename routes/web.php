<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Volt::route('/', 'admin.dashboard')->name('dashboard');
    Volt::route('/users', 'admin.users')->name('users');
    Volt::route('/analytics', 'admin.analytics')->name('analytics');
});
