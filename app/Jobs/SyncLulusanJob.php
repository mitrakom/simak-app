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

class SyncLulusanJob implements ShouldQueue
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
     * @param  string|null  $tahunKeluar  Filter by tahun keluar - supports single year '2024' or range '2020-2024'
     */
    public function __construct(
        protected int $institusiId,
        protected string $syncProcessId = '',
        protected ?string $angkatan = null,
        protected ?string $tahunKeluar = null
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_lulusan_' . $institusiId . '_', true);
        }
    }

    /**
     * Build PostgreSQL filter string for API based on angkatan and tahunKeluar
     *
     * @return string PostgreSQL filter string for Feeder API
     */
    protected function buildFilter(): string
    {
        $filters = [];

        // Build angkatan filter
        if (! empty($this->angkatan)) {
            // Check if range format (e.g., '2020-2024')
            if (str_contains($this->angkatan, '-')) {
                $parts = explode('-', $this->angkatan);
                if (count($parts) === 2) {
                    $start = trim($parts[0]);
                    $end = trim($parts[1]);

                    if (strlen($start) === 4 && strlen($end) === 4 && is_numeric($start) && is_numeric($end)) {
                        $filters[] = "angkatan >= '{$start}' and angkatan <= '{$end}'";
                    }
                }
            } elseif (strlen($this->angkatan) === 4 && is_numeric($this->angkatan)) {
                // Single year format
                $filters[] = "angkatan='{$this->angkatan}'";
            }
        }

        // Build tahunKeluar filter (from tanggal_keluar)
        if (! empty($this->tahunKeluar)) {
            // Check if range format (e.g., '2020-2024')
            if (str_contains($this->tahunKeluar, '-')) {
                $parts = explode('-', $this->tahunKeluar);
                if (count($parts) === 2) {
                    $start = trim($parts[0]);
                    $end = trim($parts[1]);

                    if (strlen($start) === 4 && strlen($end) === 4 && is_numeric($start) && is_numeric($end)) {
                        // Extract year from tanggal_keluar (format: DD-MM-YYYY)
                        $filters[] = "right(tanggal_keluar,4) >= '{$start}' and right(tanggal_keluar,4) <= '{$end}'";
                    }
                }
            } elseif (strlen($this->tahunKeluar) === 4 && is_numeric($this->tahunKeluar)) {
                // Single year format - extract year from tanggal_keluar
                $filters[] = "right(tanggal_keluar,4)='{$this->tahunKeluar}'";
            }
        }

        return implode(' and ', $filters);
    }

    /**
     * Execute the job - Create batch and dispatch record sync jobs
     */
    public function handle(FeederClient $feederClient): void
    {
        // Get institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        Log::info('SyncLulusanJob started (Batch Mode)', [
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
            'sync_process_id' => $this->syncProcessId,
            'angkatan' => $this->angkatan ?? 'all',
            'tahun_keluar' => $this->tahunKeluar ?? 'all',
        ]);

        try {
            // Set institusi for FeederClient
            $feederClient->setInstitusi($institusi);

            // Build PostgreSQL filter for server-side filtering
            $filterString = $this->buildFilter();

            Log::info('Fetching lulusan from Feeder API', [
                'institusi_slug' => $institusi->slug,
                'filter' => $filterString ?: 'none (all data)',
                'angkatan' => $this->angkatan ?? 'all',
                'tahun_keluar' => $this->tahunKeluar ?? 'all',
            ]);

            // Fetch lulusan data with server-side filter
            $response = $feederClient->getListMahasiswaLulusDO($filterString, '', 0, 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                throw new Exception('Failed to fetch lulusan data from Feeder API: ' . ($response['error_desc'] ?? 'Unknown error'));
            }

            $lulusanData = $response['data'] ?? [];
            $totalRecords = count($lulusanData);

            Log::info('Fetched lulusan data from Feeder API', [
                'institusi_slug' => $institusi->slug,
                'total_from_api' => $totalRecords,
                'angkatan_filter' => $this->angkatan ?? 'none',
                'tahun_keluar_filter' => $this->tahunKeluar ?? 'none',
                'filter_applied' => ! empty($filterString),
            ]);

            if ($totalRecords === 0) {
                Log::info('No lulusan data to sync', [
                    'institusi_slug' => $institusi->slug,
                    'angkatan' => $this->angkatan ?? 'all',
                    'tahun_keluar' => $this->tahunKeluar ?? 'all',
                ]);

                return;
            }

            // Create or retrieve existing batch progress record
            $syncType = 'lulusan';
            if ($this->angkatan && $this->tahunKeluar) {
                $syncType = "lulusan_{$this->angkatan}_{$this->tahunKeluar}";
            } elseif ($this->angkatan) {
                $syncType = "lulusan_angkatan_{$this->angkatan}";
            } elseif ($this->tahunKeluar) {
                $syncType = "lulusan_tahun_{$this->tahunKeluar}";
            }

            $batchProgress = SyncBatchProgress::firstOrCreate(
                [
                    'batch_id' => $this->syncProcessId,
                ],
                [
                    'institusi_id' => $institusi->id,
                    'sync_type' => $syncType,
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

            // Create individual sync jobs for each lulusan record
            $jobs = [];
            foreach ($lulusanData as $lulusan) {
                if (! is_array($lulusan)) {
                    continue;
                }
                $jobs[] = new SyncLulusanRecordJob(
                    $this->institusiId,
                    $lulusan,
                    $this->syncProcessId
                );
            }

            // Dispatch batch without callbacks (will be tracked via SyncLulusanRecordJob)
            $batch = Bus::batch($jobs)
                ->name('Sync Lulusan - ' . $institusi->nama)
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
            Log::error('SyncLulusanJob failed', [
                'institusi_id' => $institusi->id,
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
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncLulusanJob permanently failed', [
            'institusi_id' => $this->institusiId,
            'sync_process_id' => $this->syncProcessId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Finalize batch progress after batch completes
     * Called by scheduler or manually to ensure 100% accuracy
     */
    public static function finalizeProgress(int $institusiId): void
    {
        // Find latest processing/pending batch for this institusi + lulusan
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'like', 'lulusan%')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No lulusan batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for lulusan', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Lulusan batch not finished yet', [
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

            Log::info('Lulusan batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to finalize lulusan batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
