<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeederClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Constructor dengan Dependency Injection
     * Note: Validasi user & institusi sudah ditangani oleh middleware 'feeder.ready'
     */
    public function __construct(
        protected FeederClient $feederClient
    ) {}

    /**
     * Test koneksi dan autentikasi ke API Feeder
     * Protected by 'feeder.ready' middleware
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Set institusi dari request (disediakan oleh middleware)
            $this->feederClient->setInstitusiFromRequest($request);

            // Test koneksi dengan method baru
            $connectionTest = $this->feederClient->testConnection();

            if (! $connectionTest['success']) {
                return response()->json([
                    'message' => 'Feeder connection test failed',
                    'error' => $connectionTest['error'],
                    'institusi' => $connectionTest['institusi'] ?? null,
                ], 500);
            }

            return response()->json([
                'message' => 'Feeder connection test successful!',
                'institusi' => $connectionTest['institusi'],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Feeder connection test failed', [
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Feeder connection test failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
