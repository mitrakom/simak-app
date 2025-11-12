<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\SyncBatchProgress;
use Illuminate\Support\Facades\Log;

trait TracksBatchProgress
{
    /**
     * SIMPLIFIED: Just mark as processing (no real-time tracking to avoid race conditions)
     * Final stats will be calculated from Laravel Batch when it finishes
     */
    public function updateBatchProgress(string $batchId, bool $success = true, ?string $errorMessage = null): void
    {
        try {
            $batchProgress = SyncBatchProgress::where('batch_id', $batchId)->first();

            if (! $batchProgress) {
                Log::warning('Batch progress record not found', ['batch_id' => $batchId]);

                return;
            }

            // Only mark as processing if not already (no concurrent updates issue here)
            if ($batchProgress->status === 'pending') {
                $batchProgress->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            }

            // No more per-job tracking! Progress will be read from Laravel Batch directly
        } catch (\Exception $e) {
            Log::error('Failed to update batch status', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
