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

class SyncMahasiswaJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  int  $institusiId  Institusi ID to sync
     * @param  string  $syncProcessId  Unique sync process ID
     * @param  string|null  $angkatan  Filter by angkatan - supports single year '2024' or range '2020-2024'
     * @param  bool  $fetchBiodataDetail  Whether to fetch detailed biodata
     */
    public function __construct(
        protected int $institusiId,
        protected string $syncProcessId = '',
        protected ?string $angkatan = null,
        protected bool $fetchBiodataDetail = false
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_mahasiswa_' . $institusiId . '_', true);
        }
    }

    /**
     * Build PostgreSQL filter string for API based on angkatan
     * Supports: '2024' (single year) or '2020-2024' (range)
     *
     * @return string PostgreSQL filter string for Feeder API
     */
    protected function buildAngkatanFilter(): string
    {
        if (empty($this->angkatan)) {
            return ''; // No filter
        }

        // Check if range format (e.g., '2020-2024')
        if (str_contains($this->angkatan, '-')) {
            $parts = explode('-', $this->angkatan);
            if (count($parts) === 2) {
                $start = trim($parts[0]);
                $end = trim($parts[1]);

                // Validate years
                if (strlen($start) === 4 && strlen($end) === 4 && is_numeric($start) && is_numeric($end)) {
                    // PostgreSQL filter for range: left(id_periode,4) >= '2020' and left(id_periode,4) <= '2024'
                    return "left(id_periode,4) >= '{$start}' and left(id_periode,4) <= '{$end}'";
                }
            }
        }

        // Single year format (e.g., '2024')
        if (strlen($this->angkatan) === 4 && is_numeric($this->angkatan)) {
            // PostgreSQL filter: left(id_periode,4)='2024'
            return "left(id_periode,4)='{$this->angkatan}'";
        }

        return ''; // Invalid format, no filter
    }

    /**
     * Execute the job - Create batch and dispatch record sync jobs
     */
    public function handle(FeederClient $feederClient): void
    {
        // Get institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        Log::info('Starting SyncMahasiswaJob (Batch Mode)', [
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'sync_process_id' => $this->syncProcessId,
            'angkatan' => $this->angkatan ?? 'all',
            'fetch_biodata_detail' => $this->fetchBiodataDetail,
        ]);

        try {
            // Set institusi for FeederClient
            $feederClient->setInstitusi($institusi);

            // Build PostgreSQL filter for angkatan (server-side filtering)
            $filterString = $this->buildAngkatanFilter();

            Log::info('Fetching mahasiswa from Feeder API', [
                'institusi_slug' => $institusi->slug,
                'filter' => $filterString ?: 'none (all data)',
                'angkatan' => $this->angkatan ?? 'all',
            ]);

            // Fetch mahasiswa data with server-side filter
            $response = $feederClient->getListMahasiswa($filterString, '', 0, 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                throw new Exception('Failed to fetch mahasiswa data from Feeder API: ' . ($response['error_desc'] ?? 'Unknown error'));
            }

            $mahasiswaData = $response['data'] ?? [];
            $totalRecords = count($mahasiswaData);

            Log::info('Fetched mahasiswa data from Feeder API', [
                'institusi_slug' => $institusi->slug,
                'total_from_api' => $totalRecords,
                'angkatan_filter' => $this->angkatan ?? 'none',
                'filter_applied' => ! empty($filterString),
            ]);

            if ($totalRecords === 0) {
                Log::info('No mahasiswa data to sync', [
                    'institusi_slug' => $institusi->slug,
                    'angkatan' => $this->angkatan ?? 'all',
                ]);

                return;
            }

            // Create or retrieve existing batch progress record
            $batchProgress = SyncBatchProgress::firstOrCreate(
                [
                    'batch_id' => $this->syncProcessId,
                ],
                [
                    'institusi_id' => $institusi->id,
                    'sync_type' => $this->angkatan ? "mahasiswa_{$this->angkatan}" : 'mahasiswa',
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
                'sync_type' => $batchProgress->sync_type,
            ]);

            // Create individual sync jobs for each mahasiswa record
            $jobs = [];
            foreach ($mahasiswaData as $mahasiswa) {
                $jobs[] = new SyncMahasiswaRecordJob(
                    $this->institusiId,
                    $mahasiswa,
                    $this->syncProcessId,
                    $this->fetchBiodataDetail
                );
            }

            // Dispatch batch without callbacks (will be tracked via SyncMahasiswaRecordJob)
            $batch = Bus::batch($jobs)
                ->name('Sync Mahasiswa - ' . $institusi->nama . ($this->angkatan ? " ({$this->angkatan})" : ''))
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
                'angkatan' => $this->angkatan ?? 'all',
                'fetch_biodata_detail' => $this->fetchBiodataDetail,
            ]);
        } catch (Exception $e) {
            Log::error('SyncMahasiswaJob failed', [
                'institusi_id' => $this->institusiId,
                'sync_process_id' => $this->syncProcessId,
                'angkatan' => $this->angkatan ?? 'all',
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
        // Find latest processing/pending batch for this institusi + mahasiswa
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'like', 'mahasiswa%')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No mahasiswa batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for mahasiswa', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Mahasiswa batch not finished yet', [
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

            Log::info('Mahasiswa batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to finalize mahasiswa batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
