<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\SyncBatchProgress;
use App\Services\FeederClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SyncProdiJob implements ShouldQueue
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
            $this->syncProcessId = uniqid('sync_prodi_' . $institusiId . '_', true);
        }
    }

    /**
     * Execute the job - Create batch and dispatch record sync jobs
     */
    public function handle(FeederClient $feederClient): void
    {
        // Get institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        Log::info('Starting SyncProdiJob (Batch Mode)', [
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'sync_process_id' => $this->syncProcessId,
        ]);

        try {
            // Set institusi for FeederClient
            $feederClient->setInstitusi($institusi);

            // Fetch prodi data from Feeder API (limit = 0 means no limit)
            $response = $feederClient->fetch('GetProdi', [], '', 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                throw new \Exception('Failed to fetch prodi data from Feeder API: ' . ($response['error_desc'] ?? 'Unknown error'));
            }

            $prodiData = $response['data'] ?? [];
            $totalRecords = count($prodiData);

            Log::info('Fetched prodi data from Feeder', [
                'institusi_slug' => $institusi->slug,
                'total_prodi' => $totalRecords,
            ]);

            if ($totalRecords === 0) {
                Log::info('No prodi data to sync', ['institusi_slug' => $institusi->slug]);

                return;
            }

            // Create or retrieve existing batch progress record (for retry scenarios)
            $batchProgress = SyncBatchProgress::firstOrCreate(
                [
                    'batch_id' => $this->syncProcessId, // Unique identifier
                ],
                [
                    'institusi_id' => $institusi->id,
                    'sync_type' => 'prodi',
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

            // Create individual sync jobs for each prodi record
            $jobs = [];
            foreach ($prodiData as $prodi) {
                $jobs[] = new SyncProdiRecordJob(
                    $this->institusiId,
                    $prodi,
                    $this->syncProcessId
                );
            }

            // Dispatch batch without callbacks (will be tracked via SyncProdiRecordJob)
            $batch = Bus::batch($jobs)
                ->name('Sync Prodi - ' . $institusi->nama)
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
        } catch (\Exception $e) {
            Log::error('SyncProdiJob failed', [
                'institusi_slug' => $institusi->slug,
                'sync_process_id' => $this->syncProcessId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Finalize batch progress after batch completes
     * Called by scheduler or manually to ensure 100% accuracy
     */
    public static function finalizeProgress(int $institusiId): void
    {
        // Find latest processing/pending batch for this institusi + prodi
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'prodi')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No prodi batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for prodi', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Prodi batch not finished yet', [
                    'batch_id' => $batchProgress->batch_id,
                    'progress' => $laravelBatch->processedJobs() . '/' . $laravelBatch->totalJobs,
                ]);

                return;
            }

            // Calculate final stats from Laravel Batch (100% accurate)
            $processedRecords = $laravelBatch->totalJobs;
            $failedRecords = $laravelBatch->failedJobs;
            $successCount = $processedRecords - $failedRecords;

            // Update batch progress with final stats
            $batchProgress->update([
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
                'progress_percentage' => 100,
            ]);

            // Mark as completed or failed based on results
            $summary = [
                'message' => $failedRecords > 0 ? 'Batch completed with some failures' : 'Batch completed successfully',
                'total_records' => $batchProgress->total_records,
                'processed_records' => $processedRecords,
                'success_count' => $successCount,
                'failed_records' => $failedRecords,
            ];

            if ($failedRecords > 0) {
                $batchProgress->markFailed('Some jobs failed', $summary);
            } else {
                $batchProgress->markCompleted($summary);
            }

            Log::info('Prodi batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to finalize prodi batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
