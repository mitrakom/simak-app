<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-fix stuck batch progress every 5 minutes
// Requires Laravel scheduler to be running: php artisan schedule:work
Schedule::call(function () {
    Artisan::call('app:recalculate-batch-progress');
})->everyFiveMinutes()
    ->name('auto-fix-stuck-batches')
    ->withoutOverlapping();
