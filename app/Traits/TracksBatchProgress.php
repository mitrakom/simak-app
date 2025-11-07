<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\SyncBatchProgress;
use Illuminate\Support\Facades\Log;

trait TracksBatchProgress
{
    /**
     * Update batch progress after job completes
     * Call this method at the end of handle() in record sync jobs
     */
    public function updateBatchProgress(string $batchId, bool $success = true, string $errorMessage = null): void
    {
        try {
            Log::debug('TracksBatchProgress: Attempting to update progress', [
                'batch_id' => $batchId,
                'success' => $success,
                'error_message' => $errorMessage
            ]);

            $batchProgress = SyncBatchProgress::where('batch_id', $batchId)->first();

            if (!$batchProgress) {
                Log::warning('Batch progress record not found', ['batch_id' => $batchId]);
                return;
            }

            // Mark as processing if not already
            if ($batchProgress->status === 'pending') {
                $batchProgress->markProcessing();
            }

            // Increment processed records
            $processed = $batchProgress->processed_records + 1;
            $failed = $batchProgress->failed_records;

            if (!$success) {
                $failed++;
            }

            $batchProgress->updateProgress($processed, $failed);

            Log::debug('Batch progress updated successfully', [
                'batch_id' => $batchId,
                'processed' => $processed,
                'total' => $batchProgress->total_records,
                'percentage' => $batchProgress->progress_percentage
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update batch progress', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
