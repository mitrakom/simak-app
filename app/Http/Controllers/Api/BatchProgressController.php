<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncBatchProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BatchProgressController extends Controller
{
    /**
     * Get current batch progress for a sync type
     * GET /api/batch-progress/current/{syncType}
     */
    public function getCurrentProgress(Request $request, string $syncType): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki institusi',
                ], 422);
            }

            // Get latest batch progress for this sync type
            $progress = SyncBatchProgress::where('institusi_id', $institusi->id)
                ->where('sync_type', $syncType)
                ->orderByDesc('created_at')
                ->first();

            if (! $progress) {
                return response()->json([
                    'status' => 'success',
                    'data' => null,
                    'message' => 'No batch progress found for this sync type',
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->formatProgress($progress),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get batch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch progress by batch ID
     * GET /api/batch-progress/{batchId}
     */
    public function getProgressByBatchId(Request $request, string $batchId): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki institusi',
                ], 422);
            }

            $progress = SyncBatchProgress::where('institusi_id', $institusi->id)
                ->where('batch_id', $batchId)
                ->first();

            if (! $progress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Batch progress not found',
                    'error' => 'batch_not_found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->formatProgress($progress),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get batch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all batch progress for institusi
     * GET /api/batch-progress/history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki institusi',
                ], 422);
            }

            // Get all batch progress, latest first
            $batches = SyncBatchProgress::where('institusi_id', $institusi->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $batches->map(fn ($batch) => $this->formatProgress($batch))->toArray(),
                'total' => $batches->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get batch history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Poll batch progress (for real-time monitoring without WebSocket)
     * GET /api/batch-progress/poll/{batchId}
     */
    public function pollProgress(Request $request, string $batchId): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki institusi',
                ], 422);
            }

            $progress = SyncBatchProgress::where('institusi_id', $institusi->id)
                ->where('batch_id', $batchId)
                ->first();

            if (! $progress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Batch progress not found',
                ], 404);
            }

            // Return current progress with cache-busting headers
            return response()->json([
                'status' => 'success',
                'data' => $this->formatProgress($progress),
                'cache_bust' => now()->timestamp,
            ], 200)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to poll batch progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format progress data for API response
     */
    private function formatProgress(SyncBatchProgress $progress): array
    {
        return [
            'batch_id' => $progress->batch_id,
            'sync_type' => $progress->sync_type,
            'status' => $progress->status,
            'status_info' => $progress->getStatusWithIcon(),
            'progress' => [
                'percentage' => $progress->progress_percentage,
                'processed' => $progress->processed_records,
                'total' => $progress->total_records,
                'failed' => $progress->failed_records,
                'remaining' => $progress->total_records - $progress->processed_records,
            ],
            'summary' => $progress->summary,
            'error_message' => $progress->error_message,
            'started_at' => $progress->started_at?->toIso8601String(),
            'completed_at' => $progress->completed_at?->toIso8601String(),
            'duration_seconds' => $progress->completed_at
                ? $progress->completed_at->diffInSeconds($progress->started_at)
                : null,
            'created_at' => $progress->created_at->toIso8601String(),
        ];
    }
}
