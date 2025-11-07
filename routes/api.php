<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BroadcastTestController;
use App\Http\Controllers\Api\InstitusiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Test routes untuk broadcast (public, tidak perlu auth)
Route::prefix('broadcast-test')->group(function () {
    Route::get('/pusher-connection', [\App\Http\Controllers\Api\BroadcastTestController::class, 'testPusherConnection']);
    Route::post('/send-event', [\App\Http\Controllers\Api\BroadcastTestController::class, 'testBroadcastEvent']);
    Route::get('/debug-info', [\App\Http\Controllers\Api\BroadcastTestController::class, 'pusherDebugInfo']);
});

// Simple test route untuk broadcast
Route::get('/test-broadcast/{institusiId}', function (int $institusiId) {
    event(new \App\Events\SyncProgressUpdated(75, 'Testing broadcast message', 'test-' . time(), $institusiId));
    return response()->json([
        'message' => 'Broadcast test sent successfully',
        'institusi_id' => $institusiId,
        'timestamp' => now()->toISOString()
    ]);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::get('/test', [\App\Http\Controllers\Api\TestController::class, 'index'])->name('connection');


// Test routes untuk FeederClient - Protected by feeder.ready middleware
Route::prefix('test')->name('test.')->middleware(['auth:sanctum', 'feeder.ready'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\TestController::class, 'index'])->name('connection');
    Route::get('dosen', [\App\Http\Controllers\Api\TestController::class, 'testFetchDosen'])->name('dosen');
    Route::get('mahasiswa', [\App\Http\Controllers\Api\TestController::class, 'testFetchMahasiswa'])->name('mahasiswa');
    Route::get('sql-filter', [\App\Http\Controllers\Api\TestController::class, 'testFetchWithSqlFilter'])->name('sql.filter');
});

// Batch Progress monitoring routes - untuk real-time progress tracking
// SECURITY: Requires authentication and institusi
Route::prefix('batch-progress')->name('batch-progress.')->middleware(['auth:sanctum', 'has.institusi'])->group(function () {
    Route::get('current/{syncType}', [\App\Http\Controllers\Api\BatchProgressController::class, 'getCurrentProgress'])->name('current');
    Route::get('{batchId}', [\App\Http\Controllers\Api\BatchProgressController::class, 'getProgressByBatchId'])->name('show');
    Route::get('poll/{batchId}', [\App\Http\Controllers\Api\BatchProgressController::class, 'pollProgress'])->name('poll');
    Route::get('history/all', [\App\Http\Controllers\Api\BatchProgressController::class, 'getHistory'])->name('history');
});

// Sync routes untuk data synchronization
// SECURITY: Requires authentication, institusi, and feeder configuration
Route::prefix('sync')->name('sync.')->middleware(['auth:sanctum', 'has.institusi', 'feeder.ready'])->group(function () {
    // Status endpoints (GET) - tidak perlu feeder configuration
    Route::get('status-all', [\App\Http\Controllers\Api\SyncController::class, 'getAllStatus'])->withoutMiddleware('feeder.ready')->name('status.all');
    Route::get('{syncType}/last-status', [\App\Http\Controllers\Api\SyncController::class, 'getLastStatus'])->withoutMiddleware('feeder.ready')->name('last.status');

    // Sync action endpoints (POST) - perlu feeder configuration
    // User hanya bisa sync data institusi mereka sendiri
    Route::post('prodi', [\App\Http\Controllers\Api\SyncController::class, 'syncProdi'])->name('prodi');
    Route::post('dosen', [\App\Http\Controllers\Api\SyncController::class, 'syncDosen'])->name('dosen');
    Route::post('mahasiswa', [\App\Http\Controllers\Api\SyncController::class, 'syncMahasiswa'])->name('mahasiswa');
    Route::post('akademik-mahasiswa', [\App\Http\Controllers\Api\SyncController::class, 'syncAkademikMahasiswa'])->name('akademik.mahasiswa');
    Route::post('bimbingan-ta', [\App\Http\Controllers\Api\SyncController::class, 'syncBimbinganTa'])->name('bimbingan.ta');
    Route::post('dosen-akreditasi', [\App\Http\Controllers\Api\SyncController::class, 'syncDosenAkreditasi'])->name('dosen.akreditasi');
    Route::post('lulusan', [\App\Http\Controllers\Api\SyncController::class, 'syncLulusan'])->name('lulusan');
    Route::post('prestasi-mahasiswa', [\App\Http\Controllers\Api\SyncController::class, 'syncPrestasi'])->name('prestasi.mahasiswa');
    Route::post('aktivitas-mahasiswa', [\App\Http\Controllers\Api\SyncController::class, 'syncAktivitasMahasiswa'])->name('aktivitas.mahasiswa');

    // Admin only routes - untuk sync semua institusi atau institusi lain
    Route::post('prodi/all', [\App\Http\Controllers\Api\SyncController::class, 'syncAllProdi'])->withoutMiddleware('has.institusi')->name('prodi.all');
    Route::post('prodi/{slug}', [\App\Http\Controllers\Api\SyncController::class, 'syncProdiBySlug'])->name('prodi.slug');
});

// Public routes
Route::prefix('institusi')->name('institusi.')->group(function () {
    Route::get('options', [InstitusiController::class, 'options'])->name('options');
    Route::get('validate/{slug}', [InstitusiController::class, 'validateSlug'])->name('validate.slug');
    Route::get('{slug}', [InstitusiController::class, 'show'])->name('show');
});

// Public Authentication Routes (API)
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot.password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset.password');
});

// Protected Authentication Routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::get('profile', [AuthController::class, 'profile'])->name('profile');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout.all');
    });

    // Protected institusi routes
    Route::prefix('institusi')->name('institusi.')->group(function () {
        Route::get('/', [InstitusiController::class, 'index'])->name('index');
    });

    // Routes that require slug validation
    Route::middleware('validate.institusi.slug')->group(function () {
        // Feeder data routes dengan slug validation
        Route::prefix('{slug}/feeder')->name('feeder.')->group(function () {
            Route::get('dosen', [\App\Http\Controllers\Api\FeederDataController::class, 'getDosenByInstitusi'])->name('dosen');
            Route::get('mahasiswa', [\App\Http\Controllers\Api\FeederDataController::class, 'getMahasiswaByInstitusi'])->name('mahasiswa');
            Route::get('prodi', [\App\Http\Controllers\Api\FeederDataController::class, 'getProdiByInstitusi'])->name('prodi');
            Route::post('sync', [\App\Http\Controllers\Api\FeederDataController::class, 'syncData'])->name('sync');
        });

        // Example: Route::prefix('{slug}/dashboard')->group(function () {
        //     // Dashboard routes that require slug validation
        // });
    });

    // Legacy user endpoint (keep for backward compatibility)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
