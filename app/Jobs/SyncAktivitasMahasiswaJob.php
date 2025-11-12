<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\LprAktivitasMahasiswa;
use App\Models\SyncBatchProgress;
use App\Services\FeederClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Coordinator job untuk sinkronisasi data aktivitas mahasiswa dari Feeder API.
 *
 * Job ini menggunakan Laravel Job Batches untuk memproses data dalam parallel.
 * Mengambil data dari endpoint GetListAktivitasMahasiswa.
 *
 * Setiap aktivitas diproses oleh SyncAktivitasMahasiswaRecordJob (worker),
 * yang kemudian fetch anggota dari endpoint GetListAnggotaAktivitasMahasiswa.
 */
class SyncAktivitasMahasiswaJob implements ShouldQueue
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
     * @param  int  $institusiId  Institusi ID (multitenancy)
     * @param  string  $syncProcessId  Unique identifier untuk proses sync ini
     * @param  string|null  $semester  Filter semester (format: 20241)
     */
    public function __construct(
        protected int $institusiId,
        protected string $syncProcessId = '',
        protected ?string $semester = null
    ) {
        if (empty($this->syncProcessId)) {
            $this->syncProcessId = uniqid('sync_aktivitas_mahasiswa_' . $institusiId . '_', true);
        }

        // Set queue to 'sync'
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        $feederClient = new FeederClient;
        $feederClient->setInstitusi($institusi);

        // Prepare filter
        $filter = [];
        if ($this->semester) {
            $filter['id_semester'] = $this->semester;
        }

        // Tentukan sync_type berdasarkan filter
        $syncType = 'aktivitas_mahasiswa';
        if ($this->semester) {
            $syncType = "aktivitas_mahasiswa_semester_{$this->semester}";
        }

        Log::info('Starting sync aktivitas mahasiswa', [
            'sync_process_id' => $this->syncProcessId,
            'institusi_id' => $institusi->id,
            'sync_type' => $syncType,
            'semester' => $this->semester,
        ]);

        try {
            // Get data from Feeder API
            $response = $feederClient->getListAktivitasMahasiswa($filter, '', 0, 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                throw new \Exception($response['error_desc'] ?? 'Failed to get data from Feeder API');
            }

            $aktivitasData = $response['data'] ?? [];
            $totalRecords = count($aktivitasData);

            Log::info('Got aktivitas data from Feeder', [
                'sync_process_id' => $this->syncProcessId,
                'total' => $totalRecords,
            ]);

            // Create batch progress record FIRST - even if no data
            $batchProgress = SyncBatchProgress::firstOrCreate(
                ['batch_id' => $this->syncProcessId],
                [
                    'institusi_id' => $institusi->id,
                    'sync_type' => $syncType,
                    'status' => 'processing',
                    'total_records' => $totalRecords,
                    'processed_records' => 0,
                    'failed_records' => 0,
                    'progress_percentage' => 0,
                    'started_at' => now(),
                ]
            );

            if (empty($aktivitasData)) {
                // No data found - mark as completed immediately
                $batchProgress->update([
                    'status' => 'completed',
                    'progress_percentage' => 100,
                    'completed_at' => now(),
                ]);

                Log::warning('No aktivitas data found from Feeder', [
                    'sync_process_id' => $this->syncProcessId,
                    'semester' => $this->semester,
                ]);

                return;
            }

            // Create worker jobs for each aktivitas
            $jobs = [];
            foreach ($aktivitasData as $aktivitas) {
                $jobs[] = new SyncAktivitasMahasiswaRecordJob(
                    $this->institusiId,
                    $aktivitas,
                    $this->syncProcessId
                );
            }

            // Dispatch batch
            $batch = Bus::batch($jobs)
                ->name("Sync Aktivitas Mahasiswa - {$institusi->slug}")
                ->allowFailures()
                ->onQueue('sync')
                ->finally(function () use ($institusi, $syncType) {
                    // Update total_records with actual database count after batch completes
                    $actualCount = LprAktivitasMahasiswa::where('institusi_id', $institusi->id)->count();

                    $progress = SyncBatchProgress::where('sync_type', $syncType)
                        ->where('institusi_id', $institusi->id)
                        ->latest('created_at')
                        ->first();

                    if ($progress) {
                        $progress->update([
                            'total_records' => $actualCount,
                            'processed_records' => $actualCount,
                            'progress_percentage' => 100,
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        Log::info('Batch completed - updated with actual record count', [
                            'sync_type' => $syncType,
                            'total_records' => $actualCount,
                        ]);
                    }
                })
                ->dispatch();

            // Update batch progress with actual Laravel batch ID
            $batchProgress->update([
                'batch_id' => $batch->id,
            ]);

            Log::info('Batch dispatched for aktivitas mahasiswa sync', [
                'sync_process_id' => $this->syncProcessId,
                'batch_id' => $batch->id,
                'total_jobs' => $totalRecords,
            ]);
        } catch (\Exception $e) {
            Log::error('SyncAktivitasMahasiswaJob failed', [
                'sync_process_id' => $this->syncProcessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        // Find latest processing/pending batch for this institusi + aktivitas_mahasiswa
        $batchProgress = SyncBatchProgress::where('institusi_id', $institusiId)
            ->where('sync_type', 'like', 'aktivitas_mahasiswa%')
            ->whereIn('status', ['processing', 'pending'])
            ->latest('id')
            ->first();

        if (! $batchProgress) {
            Log::info('No aktivitas mahasiswa batch to finalize', ['institusi_id' => $institusiId]);

            return;
        }

        try {
            // Get Laravel Batch
            $laravelBatch = Bus::findBatch($batchProgress->batch_id);

            if (! $laravelBatch) {
                Log::warning('Laravel batch not found for aktivitas mahasiswa', [
                    'batch_id' => $batchProgress->batch_id,
                    'institusi_id' => $institusiId,
                ]);

                return;
            }

            // Check if batch is finished
            if (! $laravelBatch->finished()) {
                Log::info('Aktivitas mahasiswa batch not finished yet', [
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

            Log::info('Aktivitas mahasiswa batch finalized', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'processed' => $processedRecords,
                'failed' => $failedRecords,
                'status' => $batchProgress->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to finalize aktivitas mahasiswa batch progress', [
                'batch_id' => $batchProgress->batch_id,
                'institusi_id' => $institusiId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
