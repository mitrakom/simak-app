<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\SyncProgressUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class BroadcastTestController
{
    public function testPusherConnection(): JsonResponse
    {
        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );

            // Test Pusher connection dengan get channel info
            $result = $pusher->getChannelInfo('test-channel');

            return response()->json([
                'status' => 'success',
                'message' => 'Pusher connection successful',
                'pusher_config' => [
                    'app_id' => config('broadcasting.connections.pusher.app_id'),
                    'key' => config('broadcasting.connections.pusher.key'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'encrypted' => config('broadcasting.connections.pusher.options.encrypted'),
                ],
                'channel_info' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Pusher connection test failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Pusher connection failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testBroadcastEvent(Request $request): JsonResponse
    {
        $institusiId = $request->input('institusi_id', 1);
        $progress = $request->input('progress', 50);
        $message = $request->input('message', 'Test broadcast message');

        try {
            $syncProcessId = 'test-'.time();

            // Trigger event
            event(new SyncProgressUpdated($progress, $message, $syncProcessId, $institusiId));

            Log::info('Test broadcast event triggered', [
                'institusi_id' => $institusiId,
                'progress' => $progress,
                'message' => $message,
                'sync_process_id' => $syncProcessId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Broadcast event sent successfully',
                'data' => [
                    'institusi_id' => $institusiId,
                    'progress' => $progress,
                    'message' => $message,
                    'sync_process_id' => $syncProcessId,
                    'channels' => [
                        "private-sync-process.{$syncProcessId}",
                        "private-institusi-sync.{$institusiId}",
                    ],
                    'timestamp' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Test broadcast event failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Broadcast event failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pusherDebugInfo(): JsonResponse
    {
        return response()->json([
            'broadcasting_driver' => config('broadcasting.default'),
            'pusher_config' => [
                'app_id' => config('broadcasting.connections.pusher.app_id'),
                'key' => config('broadcasting.connections.pusher.key'),
                'secret' => config('broadcasting.connections.pusher.secret') ? '***hidden***' : 'NOT SET',
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'host' => config('broadcasting.connections.pusher.options.host'),
                'port' => config('broadcasting.connections.pusher.options.port'),
                'scheme' => config('broadcasting.connections.pusher.options.scheme'),
                'encrypted' => config('broadcasting.connections.pusher.options.encrypted'),
                'useTLS' => config('broadcasting.connections.pusher.options.useTLS'),
            ],
            'env_vars' => [
                'BROADCAST_DRIVER' => env('BROADCAST_DRIVER'),
                'PUSHER_APP_ID' => env('PUSHER_APP_ID'),
                'PUSHER_APP_KEY' => env('PUSHER_APP_KEY'),
                'PUSHER_APP_SECRET' => env('PUSHER_APP_SECRET') ? '***hidden***' : 'NOT SET',
                'PUSHER_APP_CLUSTER' => env('PUSHER_APP_CLUSTER'),
            ],
        ]);
    }
}
