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

/**
 * Coordinator job untuk sinkronisasi data dosen akreditasi dari Feeder API.
 *
 * Job ini menggunakan Laravel Job Batches untuk memproses data dalam parallel.
 * Mengambil data dari 3 endpoint berbeda:
 * 1. GetRiwayatPendidikanDosen
 * 2. GetRiwayatFungsionalDosen
 * 3. GetRiwayatSertifikasiDosen
 *
 * Setiap record diproses oleh SyncDosenAkreditasiRecordJob (worker).
 */
class SyncDosenAkreditasiJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  int  $institusiId  Institusi ID yang akan di-sync
     * @param  string  $syncProcessId  Unique identifier untuk proses sync ini
     * @param  int|null  $limit  Limit jumlah record yang di-fetch (untuk testing)
     * @param  string|null  $nidnFilter  Filter by NIDN (exact match atau prefix dengan %)
     */
    public function __construct(
        protected int $institusiId,
        protected string $syncProcessId = '',
        protected ?int $limit = null,
        protected ?string $nidnFilter = null
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_dosen_akreditasi_' . $institusiId . '_', true);
        }
    }

    /**
     * Execute the job - Create batch and dispatch record sync jobs
     */
    public function handle(FeederClient $feederClient): void
    {
        // Get institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        Log::info('SyncDosenAkreditasiJob started', [
            'sync_process_id' => $this->syncProcessId,
            'institusi_id' => $institusi->id,
            'institusi_slug' => $institusi->slug,
        ]);

        try {
            // Set institusi for FeederClient
            $feederClient->setInstitusi($institusi);

            $allRecords = [];

            // 1. Fetch data pendidikan
            $pendidikanData = $this->fetchAllData(
                $feederClient,
                'getRiwayatPendidikanDosen',
                'pendidikan',
                $this->limit,
                $this->nidnFilter
            );
            foreach ($pendidikanData as $record) {
                $allRecords[] = ['data' => $record, 'type' => 'pendidikan'];
            }

            // 2. Fetch data jabatan fungsional
            $fungsionalData = $this->fetchAllData(
                $feederClient,
                'getRiwayatFungsionalDosen',
                'fungsional',
                $this->limit,
                $this->nidnFilter
            );
            foreach ($fungsionalData as $record) {
                $allRecords[] = ['data' => $record, 'type' => 'fungsional'];
            }

            // 3. Fetch data sertifikasi
            $sertifikasiData = $this->fetchAllData(
                $feederClient,
                'getRiwayatSertifikasiDosen',
                'sertifikasi',
                $this->limit,
                $this->nidnFilter
            );
            foreach ($sertifikasiData as $record) {
                $allRecords[] = ['data' => $record, 'type' => 'sertifikasi'];
            }

            $totalRecords = count($allRecords);

            Log::info('All data fetched from Feeder API', [
                'sync_process_id' => $this->syncProcessId,
                'pendidikan_count' => count($pendidikanData),
                'fungsional_count' => count($fungsionalData),
                'sertifikasi_count' => count($sertifikasiData),
                'total_records' => $totalRecords,
            ]);

            if ($totalRecords === 0) {
                Log::warning('No dosen akreditasi data to sync', [
                    'sync_process_id' => $this->syncProcessId,
                    'institusi_slug' => $institusi->slug,
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
                    'sync_type' => 'dosen_akreditasi',
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

            // Create worker jobs for each record
            $jobs = [];
            foreach ($allRecords as $item) {
                $jobs[] = new SyncDosenAkreditasiRecordJob(
                    $this->institusiId,
                    $item['data'],
                    $item['type'],
                    $this->syncProcessId
                );
            }

            // Dispatch batch without callbacks (will be tracked via SyncDosenAkreditasiRecordJob)
            $batch = Bus::batch($jobs)
                ->name('Sync Dosen Akreditasi - ' . $institusi->nama)
                ->dispatch();

            // Update batch progress with actual Laravel batch ID
            $batchProgress->update([
                'batch_id' => $batch->id,
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Batch dispatched for dosen akreditasi sync', [
                'sync_process_id' => $this->syncProcessId,
                'batch_id' => $batch->id,
                'total_jobs' => count($jobs),
                'institusi_slug' => $institusi->slug,
            ]);
        } catch (\Exception $e) {
            Log::error('SyncDosenAkreditasiJob failed', [
                'sync_process_id' => $this->syncProcessId,
                'institusi_id' => $this->institusiId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch all data from a specific endpoint
     */
    protected function fetchAllData(
        FeederClient $feederClient,
        string $method,
        string $dataType,
        ?int $limit = null,
        ?string $nidnFilter = null
    ): array {
        Log::info("Fetching {$dataType} data from Feeder API", [
            'limit' => $limit ?? 'unlimited',
            'nidn_filter' => $nidnFilter ?? 'none',
        ]);

        $allData = [];
        $offset = 0;
        $batchSize = 500; // Fetch in larger batches
        $batchNumber = 1;

        // Build filter for NIDN
        $filter = [];
        if ($nidnFilter) {
            // Support exact match or prefix with %
            if (str_contains($nidnFilter, '%')) {
                $filter['nidn'] = $nidnFilter;
            } else {
                $filter['nidn'] = $nidnFilter;
            }
        }

        do {
            // Apply limit if set
            if ($limit !== null && count($allData) >= $limit) {
                Log::info("Reached limit for {$dataType}", [
                    'limit' => $limit,
                    'fetched' => count($allData),
                ]);
                break;
            }

            // Adjust batch size if approaching limit
            $remainingLimit = $limit !== null ? ($limit - count($allData)) : $batchSize;
            $currentBatchSize = min($batchSize, $remainingLimit);

            Log::info("Fetching {$dataType} batch {$batchNumber}", [
                'offset' => $offset,
                'batch_size' => $currentBatchSize,
                'fetched_so_far' => count($allData),
                'filter' => ! empty($filter) ? json_encode($filter) : 'none',
            ]);

            // Call the appropriate method on FeederClient
            $response = $feederClient->$method($filter, '', $currentBatchSize, $offset);

            if (! $response) {
                Log::warning("No data received from {$method} API");
                break;
            }

            // Handle response structure
            $batchData = [];
            $totalAvailable = 0;

            if (isset($response['data']) && is_array($response['data'])) {
                $batchData = $response['data'];
                $totalAvailable = $response['jumlah'] ?? count($batchData);
            } elseif (is_array($response)) {
                $batchData = $response;
                $totalAvailable = count($batchData);
            }

            $batchCount = count($batchData);

            Log::info("API returned {$batchCount} {$dataType} records", [
                'batch_number' => $batchNumber,
                'offset' => $offset,
                'total_available' => $totalAvailable,
            ]);

            if ($batchCount === 0) {
                break;
            }

            $allData = array_merge($allData, $batchData);
            $offset += $batchSize;
            $batchNumber++;

            // Safety break
            if ($batchNumber > 100) {
                Log::warning("Reached maximum batch limit for {$dataType} data");
                break;
            }

            // Break if end of data
            if ($batchCount < $batchSize || ($totalAvailable > 0 && $offset >= $totalAvailable)) {
                break;
            }
        } while (true);

        Log::info("Completed fetching {$dataType} data", [
            'total_records' => count($allData),
            'total_batches' => $batchNumber - 1,
        ]);

        return $allData;
    }

    /**
     * Finalize batch progress after batch completes
     * Called by scheduler or manually to ensure 100% accuracy
     */
    public static function finalizeProgress(int $institusiId): void
    {
        // Find latest processing/pending batch for this institusi + dosen_akreditasi
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'dosen_akreditasi')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No dosen akreditasi batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for dosen akreditasi', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Dosen akreditasi batch not finished yet', [
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

            Log::info('Dosen akreditasi batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to finalize dosen akreditasi batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncDosenAkreditasiJob permanently failed', [
            'institusi_id' => $this->institusiId,
            'sync_process_id' => $this->syncProcessId,
            'error' => $exception->getMessage(),
        ]);
    }
}
