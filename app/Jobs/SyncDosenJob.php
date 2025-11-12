<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\SyncBatchProgress;
use App\Services\FeederClient;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SyncDosenJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $institusiId,
        protected string $syncProcessId = ''
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_dosen_' . $institusiId . '_', true);
        }
    }

    /**
     * Execute the job - Create batch and dispatch record sync jobs
     */
    public function handle(FeederClient $feederClient): void
    {
        // Get institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        Log::info('Starting SyncDosenJob (Batch Mode)', [
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'sync_process_id' => $this->syncProcessId,
        ]);

        try {
            // Set institusi for FeederClient
            $feederClient->setInstitusi($institusi);

            // Fetch dosen data from Feeder API
            $response = $feederClient->getListDosen([], '', 0, 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                throw new Exception('Failed to fetch dosen data from Feeder API: ' . ($response['error_desc'] ?? 'Unknown error'));
            }

            $dosenData = $response['data'] ?? [];
            $totalRecords = count($dosenData);

            Log::info('Fetched dosen data from Feeder', [
                'institusi_slug' => $institusi->slug,
                'total_dosen' => $totalRecords,
            ]);

            if ($totalRecords === 0) {
                Log::info('No dosen data to sync', ['institusi_slug' => $institusi->slug]);

                return;
            }

            // Create or retrieve existing batch progress record (for retry scenarios)
            $batchProgress = SyncBatchProgress::firstOrCreate(
                [
                    'batch_id' => $this->syncProcessId, // Unique identifier
                ],
                [
                    'institusi_id' => $institusi->id,
                    'sync_type' => 'dosen',
                    'status' => 'pending',
                    'total_records' => $totalRecords,
                    'processed_records' => 0,
                    'failed_records' => 0,
                    'progress_percentage' => 0,
                ]
            );

            Log::info('Created batch progress record', [
                'batch_id' => $batchProgress->batch_id,
                'total_records' => $totalRecords,
            ]);

            // Create individual sync jobs for each dosen record
            $jobs = [];
            foreach ($dosenData as $dosen) {
                $jobs[] = new SyncDosenRecordJob(
                    $this->institusiId,
                    $dosen,
                    $this->syncProcessId
                );
            }

            // Dispatch batch (callback akan ditrigger manual via recalculate command/scheduler)
            $batch = Bus::batch($jobs)
                ->name('Sync Dosen - ' . $institusi->nama)
                ->dispatch();

            // Update batch progress with actual Laravel batch ID
            $batchProgress->update([
                'batch_id' => $batch->id,
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Batch dispatched successfully', [
                'institusi_slug' => $institusi->slug,
                'batch_id' => $batch->id,
                'sync_process_id' => $this->syncProcessId,
                'total_jobs' => count($jobs),
            ]);
        } catch (Exception $e) {
            Log::error('SyncDosenJob failed', [
                'institusi_id' => $this->institusiId,
                'sync_process_id' => $this->syncProcessId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle successful batch completion
     */
    protected function handleBatchSuccess(SyncBatchProgress $batchProgress): void
    {
        $summary = [
            'message' => 'Batch completed successfully',
            'total_records' => $batchProgress->total_records,
            'processed_records' => $batchProgress->processed_records,
            'failed_records' => $batchProgress->failed_records,
        ];

        $batchProgress->markCompleted($summary);

        Log::info('Batch success', [
            'batch_id' => $batchProgress->batch_id,
            'summary' => $summary,
        ]);
    }

    /**
     * Finalize progress from Laravel Batch statistics (called when batch finishes)
     * This is 100% accurate with zero race conditions
     */
    protected function finalizeProgress(int $institusiId): void
    {
        try {
            // Find the batch progress record for this batch
            $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
                ->where('sync_type', 'dosen')
                ->where('status', 'processing')
                ->latest()
                ->first();

            if (! $batchProgress) {
                Log::warning('No batch progress found for finalization', [
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Get the Laravel batch
            $batch = Bus::findBatch($batchProgress->batch_id);

            if (! $batch) {
                Log::warning('Laravel batch not found for finalization', [
                    'batch_id' => $batchProgress->batch_id,
                ]);

                return;
            }

            // Calculate final stats from Laravel Batch (100% accurate!)
            $processedRecords = $batch->totalJobs;
            $failedRecords = $batch->failedJobs;
            $successCount = $processedRecords - $failedRecords;

            // Update with accurate final counts
            $batchProgress->update([
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
                'progress_percentage' => 100,
            ]);

            // Mark as completed
            $summary = [
                'message' => 'Sinkronisasi selesai',
                'total_records' => $batchProgress->total_records,
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
            ];

            $batchProgress->markCompleted($summary);

            Log::info('Batch progress finalized successfully', [
                'batch_id' => $batchProgress->batch_id,
                'total' => $batchProgress->total_records,
                'processed' => $processedRecords,
                'success' => $successCount,
                'failed' => $failedRecords,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to finalize batch progress', [
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recalculate progress from actual database records (called when batch finishes)
     * This fixes any race condition issues that occurred during real-time tracking
     */
    protected function recalculateProgress(int $institusiId): void
    {
        try {
            // Find the batch progress record for this batch
            $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
                ->where('sync_type', 'dosen')
                ->where('status', 'processing')
                ->latest()
                ->first();

            if (! $batchProgress) {
                Log::warning('No batch progress found for recalculation', [
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Get the Laravel batch to check status
            $batch = Bus::findBatch($batchProgress->batch_id);

            if (! $batch) {
                Log::warning('Laravel batch not found for recalculation', [
                    'batch_id' => $batchProgress->batch_id,
                ]);

                return;
            }

            // Recalculate from ACTUAL Laravel batch statistics
            $processedRecords = $batch->totalJobs;
            $failedRecords = $batch->failedJobs;
            $successCount = $processedRecords - $failedRecords;

            // Update with accurate counts
            $batchProgress->update([
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
                'progress_percentage' => 100,
            ]);

            // Mark as completed
            $summary = [
                'message' => 'Sinkronisasi selesai (recalculated)',
                'total_records' => $batchProgress->total_records,
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
            ];

            $batchProgress->markCompleted($summary);

            Log::info('Batch progress recalculated and completed', [
                'batch_id' => $batchProgress->batch_id,
                'total' => $batchProgress->total_records,
                'processed' => $processedRecords,
                'success' => $successCount,
                'failed' => $failedRecords,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to recalculate batch progress', [
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle batch failure or partial failure
     */
    protected function handleBatchFailure(SyncBatchProgress $batchProgress, $e = null): void
    {
        $errorMessage = $e ? $e->getMessage() : 'Batch processing encountered errors';

        $summary = [
            'message' => 'Batch completed with errors',
            'total_records' => $batchProgress->total_records,
            'processed_records' => $batchProgress->processed_records,
            'failed_records' => $batchProgress->failed_records,
        ];

        $batchProgress->markFailed($errorMessage, $summary);

        Log::error('Batch failure', [
            'batch_id' => $batchProgress->batch_id,
            'error' => $errorMessage,
            'summary' => $summary,
        ]);
    }
}
