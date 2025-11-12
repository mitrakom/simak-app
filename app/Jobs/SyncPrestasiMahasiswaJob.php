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
 * Coordinator job untuk sinkronisasi data prestasi mahasiswa dari Feeder API.
 *
 * Job ini menggunakan Laravel Job Batches untuk memproses data dalam parallel.
 * Mengambil data dari endpoint GetListPrestasiMahasiswa.
 *
 * Setiap record diproses oleh SyncPrestasiMahasiswaRecordJob (worker).
 */
class SyncPrestasiMahasiswaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Job timeout in seconds (20 minutes)
     */
    public int $timeout = 1200;

    /**
     * Number of retries if job fails
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  Institusi  $institusi  Institusi yang akan di-sync
     * @param  string  $syncProcessId  Unique identifier untuk proses sync ini
     * @param  int|null  $tahunPrestasi  Optional filter by tahun prestasi
     */
    public function __construct(
        protected Institusi $institusi,
        protected string $syncProcessId = '',
        protected ?int $tahunPrestasi = null
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_prestasi_mahasiswa_', true);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SyncPrestasiMahasiswaJob started', [
            'sync_process_id' => $this->syncProcessId,
            'institusi_id' => $this->institusi->id,
            'institusi_slug' => $this->institusi->slug,
            'tahun_prestasi' => $this->tahunPrestasi,
        ]);

        try {
            // Initialize FeederClient
            $feederClient = new FeederClient;
            $feederClient->setInstitusi($this->institusi);

            // Fetch all data from API
            $allPrestasiData = $this->fetchAllPrestasiData($feederClient);
            $totalRecords = count($allPrestasiData);

            Log::info('All prestasi mahasiswa data fetched from Feeder API', [
                'sync_process_id' => $this->syncProcessId,
                'total_records' => $totalRecords,
                'tahun_prestasi' => $this->tahunPrestasi,
            ]);

            if ($totalRecords === 0) {
                Log::warning('No prestasi mahasiswa data to sync', [
                    'sync_process_id' => $this->syncProcessId,
                    'institusi_slug' => $this->institusi->slug,
                    'tahun_prestasi' => $this->tahunPrestasi,
                ]);

                return;
            }

            // Determine sync type based on filter
            $syncType = $this->tahunPrestasi
                ? "prestasi_mahasiswa_tahun_{$this->tahunPrestasi}"
                : 'prestasi_mahasiswa';

            // Create or retrieve existing batch progress record (for retry scenarios)
            $batchProgress = SyncBatchProgress::firstOrCreate(
                [
                    'batch_id' => $this->syncProcessId, // Use syncProcessId as unique identifier
                ],
                [
                    'institusi_id' => $this->institusi->id,
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
                'sync_process_id' => $this->syncProcessId,
                'total_records' => $totalRecords,
            ]);

            // Create worker jobs for each record
            $jobs = [];
            foreach ($allPrestasiData as $prestasiRecord) {
                $jobs[] = new SyncPrestasiMahasiswaRecordJob(
                    $this->institusi,
                    $prestasiRecord,
                    $this->syncProcessId
                );
            }

            // Dispatch batch without callbacks (will be tracked via SyncPrestasiMahasiswaRecordJob)
            $batch = Bus::batch($jobs)
                ->name("Sync Prestasi Mahasiswa - {$this->institusi->slug}")
                ->onQueue('sync')
                ->dispatch();

            // Update batch progress with actual Laravel batch ID
            $batchProgress->update([
                'batch_id' => $batch->id,
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Batch dispatched for prestasi mahasiswa sync', [
                'sync_process_id' => $this->syncProcessId,
                'batch_id' => $batch->id,
                'total_jobs' => $totalRecords,
                'institusi_slug' => $this->institusi->slug,
                'tahun_prestasi' => $this->tahunPrestasi,
            ]);
        } catch (\Exception $e) {
            Log::error('SyncPrestasiMahasiswaJob failed', [
                'sync_process_id' => $this->syncProcessId,
                'institusi_slug' => $this->institusi->slug,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch all prestasi mahasiswa data from Feeder API
     */
    protected function fetchAllPrestasiData(FeederClient $feederClient): array
    {
        Log::info('Fetching prestasi mahasiswa data from Feeder API', [
            'tahun_prestasi' => $this->tahunPrestasi,
        ]);

        $filter = [];
        if ($this->tahunPrestasi !== null) {
            $filter['tahun_prestasi'] = $this->tahunPrestasi;
        }

        $allData = [];
        $offset = 0;
        $batchSize = 500; // Fetch in larger batches
        $batchNumber = 1;

        do {
            Log::info("Fetching prestasi batch {$batchNumber}", [
                'offset' => $offset,
                'batch_size' => $batchSize,
                'filter' => $filter,
            ]);

            // Fetch data from endpoint
            $response = $feederClient->getListPrestasiMahasiswa($filter, '', $batchSize, $offset);

            if (! $response) {
                Log::warning('No data received from GetListPrestasiMahasiswa API');
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

            Log::info("API returned {$batchCount} prestasi records", [
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
                Log::warning('Reached maximum batch limit for prestasi data');
                break;
            }

            // Break if end of data
            if ($batchCount < $batchSize || ($totalAvailable > 0 && $offset >= $totalAvailable)) {
                break;
            }
        } while (true);

        Log::info('Completed fetching prestasi data', [
            'total_records' => count($allData),
            'total_batches' => $batchNumber - 1,
            'filter' => $filter,
        ]);

        return $allData;
    }

    /**
     * Finalize batch progress after batch completes
     * Called by scheduler or manually to ensure 100% accuracy
     */
    public static function finalizeProgress(int $institusiId): void
    {
        // Find latest processing/pending batch for this institusi + prestasi_mahasiswa
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'like', 'prestasi_mahasiswa%')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No prestasi mahasiswa batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for prestasi mahasiswa', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Prestasi mahasiswa batch not finished yet', [
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

            Log::info('Prestasi mahasiswa batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to finalize prestasi mahasiswa batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful batch completion
     */
    protected function handleBatchSuccess($batch, SyncBatchProgress $batchProgress): void
    {
        $batchProgress->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Log::info('Prestasi mahasiswa sync batch completed successfully', [
            'sync_process_id' => $this->syncProcessId,
            'batch_id' => $batch->id,
            'institusi_slug' => $this->institusi->slug,
            'total_jobs' => $batch->totalJobs,
            'processed_jobs' => $batch->processedJobs(),
            'tahun_prestasi' => $this->tahunPrestasi,
        ]);
    }

    /**
     * Handle batch failure
     */
    protected function handleBatchFailure($batch, \Throwable $e, SyncBatchProgress $batchProgress): void
    {
        $batchProgress->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ]);

        Log::error('Prestasi mahasiswa sync batch failed', [
            'sync_process_id' => $this->syncProcessId,
            'batch_id' => $batch->id,
            'institusi_slug' => $this->institusi->slug,
            'error' => $e->getMessage(),
            'failed_jobs' => $batch->failedJobs,
            'tahun_prestasi' => $this->tahunPrestasi,
        ]);
    }
}
