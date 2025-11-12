<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\Prodi;
use App\Traits\TracksBatchProgress;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProdiRecordJob implements ShouldQueue
{
    use Batchable, Queueable, TracksBatchProgress;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederProdi,
        protected ?string $batchIdValue = null
    ) {}

    /**
     * Execute the job - sync single prodi record
     */
    public function handle(): void
    {
        // Skip if batch is cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Get the actual Laravel batch ID
        $actualBatchId = $this->batch()?->id;

        // Get institusi from ID
        $institusi = Institusi::find($this->institusiId);

        if (! $institusi) {
            Log::error('Institusi not found for SyncProdiRecordJob', [
                'institusi_id' => $this->institusiId,
                'batch_id' => $actualBatchId,
            ]);

            // Update batch progress to mark this record as failed
            if ($actualBatchId) {
                $this->updateBatchProgress($actualBatchId, false, 'Institusi not found');
            }

            return;
        }

        $success = false;
        $errorMessage = null;

        try {
            // updateOrCreate: upsert based on unique constraint (institusi_id, id_prodi)
            $prodi = Prodi::updateOrCreate(
                [
                    'institusi_id' => $institusi->id,
                    'id_prodi' => $this->feederProdi['id_prodi'], // UUID from API
                ],
                [
                    'kode_program_studi' => $this->feederProdi['kode_program_studi'],
                    'nama_program_studi' => $this->feederProdi['nama_program_studi'],
                    'status' => $this->feederProdi['status'],
                    'id_jenjang_pendidikan' => $this->feederProdi['id_jenjang_pendidikan'],
                    'nama_jenjang_pendidikan' => $this->feederProdi['nama_jenjang_pendidikan'],
                ]
            );

            Log::debug('Prodi synced', [
                'institusi_id' => $institusi->id,
                'id_prodi' => $this->feederProdi['id_prodi'],
                'nama_program_studi' => $prodi->nama_program_studi,
                'was_recently_created' => $prodi->wasRecentlyCreated,
            ]);

            $success = true;
        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();

            Log::error('Failed to sync individual prodi record', [
                'institusi_id' => $institusi->id,
                'feeder_prodi_id' => $this->feederProdi['id_prodi'] ?? 'unknown',
                'batch_id' => $actualBatchId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Always update batch progress if batch ID is available
            if ($actualBatchId) {
                $this->updateBatchProgress($actualBatchId, $success, $errorMessage);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        $actualBatchId = $this->batch()?->id;

        Log::error('SyncProdiRecordJob failed', [
            'batch_id' => $actualBatchId,
            'feeder_prodi' => $this->feederProdi,
            'error' => $exception->getMessage(),
        ]);

        if ($actualBatchId) {
            $this->updateBatchProgress($actualBatchId, false, $exception?->getMessage() ?? 'Job failed');
        }
    }
}
