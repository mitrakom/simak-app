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

class SyncAkademikMahasiswaJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  int  $institusiId  Institusi ID to sync (multitenancy)
     * @param  string  $syncProcessId  Unique sync process ID
     * @param  string|null  $semester  Filter by semester (id_semester), e.g., '20241'
     */
    public function __construct(
        protected int $institusiId,
        protected string $syncProcessId = '',
        protected ?string $semester = null
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_akademik_mahasiswa_' . $institusiId . '_', true);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(FeederClient $feederClient): void
    {
        // Increase memory limit for this job (512MB should be enough)
        ini_set('memory_limit', '512M');

        // Get institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        Log::info('Starting SyncAkademikMahasiswaJob (Batch Mode)', [
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'semester' => $this->semester ?? 'all',
            'sync_process_id' => $this->syncProcessId,
        ]);

        try {
            // Set institusi for FeederClient
            $feederClient->setInstitusi($institusi);

            // Build filter for API
            $filter = [];
            if ($this->semester) {
                $filter['id_semester'] = $this->semester;
            }

            // Fetch akademik mahasiswa data from Feeder API
            // Use limit/offset to handle large datasets
            $limit = 1000; // Fetch 1000 records per request
            $offset = 0;
            $allRecords = [];

            Log::info('Starting to fetch akademik data', [
                'institusi_slug' => $institusi->slug,
                'filter' => $filter,
            ]);

            // Fetch all data in chunks to avoid memory issues
            do {
                $response = $feederClient->getListPerkuliahanMahasiswa($filter, '', $limit, $offset);

                if (! $response || ($response['error_code'] ?? 0) != 0) {
                    throw new Exception('Failed to fetch akademik mahasiswa data from Feeder API: ' . ($response['error_desc'] ?? 'Unknown error'));
                }

                $chunk = $response['data'] ?? [];
                $chunkCount = count($chunk);

                if ($chunkCount > 0) {
                    $allRecords = array_merge($allRecords, $chunk);
                    $offset += $limit;

                    Log::info('Fetched chunk', [
                        'chunk_size' => $chunkCount,
                        'total_so_far' => count($allRecords),
                        'offset' => $offset,
                    ]);
                }

                // Break if we got less than limit (last chunk)
                if ($chunkCount < $limit) {
                    break;
                }

                // Safety limit: max 50,000 records per sync
                if ($offset >= 50000) {
                    Log::warning('Reached safety limit of 50,000 records', [
                        'institusi_slug' => $institusi->slug,
                    ]);
                    break;
                }
            } while (true);

            $totalRecords = count($allRecords);

            Log::info('Fetched akademik mahasiswa data from Feeder', [
                'institusi_slug' => $institusi->slug,
                'total_records' => $totalRecords,
            ]);

            if ($totalRecords === 0) {
                Log::info('No akademik data to sync', ['institusi_slug' => $institusi->slug]);

                return;
            }

            // Create or retrieve existing batch progress record (for retry scenarios)
            $batchProgress = SyncBatchProgress::firstOrCreate(
                [
                    'batch_id' => $this->syncProcessId, // Unique identifier
                ],
                [
                    'institusi_id' => $institusi->id,
                    'sync_type' => 'akademik_mahasiswa',
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

            // Create individual sync jobs for each akademik record
            $jobs = [];
            foreach ($allRecords as $record) {
                if (! is_array($record)) {
                    continue;
                }
                $jobs[] = new SyncAkademikMahasiswaRecordJob(
                    $institusi->id,
                    $record,
                    $this->syncProcessId
                );
            }

            Log::info('Created worker jobs', [
                'total_jobs' => count($jobs),
                'institusi_slug' => $institusi->slug,
            ]);

            // Dispatch batch without callbacks (tracking via SyncAkademikMahasiswaRecordJob)
            $batch = Bus::batch($jobs)
                ->name('Sync Akademik Mahasiswa - ' . $institusi->nama)
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
            Log::error('SyncAkademikMahasiswaJob failed', [
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
        // Find latest processing/pending batch for this institusi + akademik_mahasiswa
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'akademik_mahasiswa')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No akademik mahasiswa batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for akademik mahasiswa', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Akademik mahasiswa batch not finished yet', [
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

            Log::info('Akademik mahasiswa batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to finalize akademik mahasiswa batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
