<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Dosen;
use App\Models\Institusi;
use App\Traits\TracksBatchProgress;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncDosenRecordJob implements ShouldQueue
{
    use Batchable, Queueable, TracksBatchProgress;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederDosen,
        protected ?string $batchIdValue = null
    ) {}

    /**
     * Execute the job - Process single dosen record
     */
    public function handle(): void
    {
        // Check if batch has been cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Get the actual Laravel batch ID (not the sync process ID)
        $actualBatchId = $this->batch()?->id;

        // Get institusi from ID
        $institusi = Institusi::find($this->institusiId);

        if (! $institusi) {
            Log::error('Institusi not found for SyncDosenRecordJob', [
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
            DB::transaction(function () use ($institusi, $actualBatchId, &$success) {
                // Direct API-to-DB mapping (no translation needed)
                $dosenData = [
                    'institusi_id' => $institusi->id,
                    'id_dosen' => $this->feederDosen['id_dosen'],
                    'nidn' => $this->feederDosen['nidn'] ?: null,
                    'nuptk' => $this->feederDosen['nuptk'] ?: null,
                    'nip' => $this->feederDosen['nip'] ?: null,
                    'nama_dosen' => $this->feederDosen['nama_dosen'],
                    'jenis_kelamin' => $this->feederDosen['jenis_kelamin'] ?: null,
                    'id_agama' => $this->feederDosen['id_agama'] ?: null,
                    'nama_agama' => $this->feederDosen['nama_agama'] ?: null,
                    'tanggal_lahir' => $this->parseDate($this->feederDosen['tanggal_lahir'] ?? null),
                    'id_status_aktif' => $this->feederDosen['id_status_aktif'] ?: null,
                    'nama_status_aktif' => $this->feederDosen['nama_status_aktif'] ?: null,
                ];

                // Cari dosen berdasarkan id_dosen dan institusi_id
                $existingDosen = Dosen::where('institusi_id', $institusi->id)
                    ->where('id_dosen', $this->feederDosen['id_dosen'])
                    ->first();

                if ($existingDosen) {
                    // Update existing dosen
                    $wasUpdated = false;

                    // Cek apakah ada perubahan data
                    if (
                        $existingDosen->nama_dosen !== $dosenData['nama_dosen'] ||
                        $existingDosen->nidn !== $dosenData['nidn'] ||
                        $existingDosen->nuptk !== $dosenData['nuptk'] ||
                        $existingDosen->nip !== $dosenData['nip'] ||
                        $existingDosen->jenis_kelamin !== $dosenData['jenis_kelamin'] ||
                        $existingDosen->id_agama !== $dosenData['id_agama'] ||
                        $existingDosen->nama_agama !== $dosenData['nama_agama'] ||
                        $existingDosen->tanggal_lahir?->format('Y-m-d') !== $dosenData['tanggal_lahir']?->format('Y-m-d') ||
                        $existingDosen->id_status_aktif !== $dosenData['id_status_aktif'] ||
                        $existingDosen->nama_status_aktif !== $dosenData['nama_status_aktif']
                    ) {
                        $existingDosen->update($dosenData);
                        $wasUpdated = true;

                        Log::debug('Dosen updated', [
                            'institusi_id' => $institusi->id,
                            'id_dosen' => $this->feederDosen['id_dosen'],
                            'nama_dosen' => $dosenData['nama_dosen'],
                            'nidn' => $dosenData['nidn'],
                            'batch_id' => $actualBatchId,
                        ]);
                    } else {
                        Log::debug('Dosen unchanged', [
                            'institusi_id' => $institusi->id,
                            'id_dosen' => $this->feederDosen['id_dosen'],
                            'nama_dosen' => $dosenData['nama_dosen'],
                            'nidn' => $dosenData['nidn'],
                            'batch_id' => $actualBatchId,
                        ]);
                    }
                } else {
                    // Create new dosen
                    Dosen::create($dosenData);

                    Log::debug('Dosen created', [
                        'institusi_id' => $institusi->id,
                        'id_dosen' => $this->feederDosen['id_dosen'],
                        'nama_dosen' => $dosenData['nama_dosen'],
                        'nidn' => $dosenData['nidn'],
                        'batch_id' => $actualBatchId,
                    ]);
                }

                $success = true;
            });
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            Log::error('Failed to sync individual dosen', [
                'institusi_id' => $this->institusiId,
                'feeder_dosen_id' => $this->feederDosen['id_dosen'] ?? 'unknown',
                'feeder_dosen_nama' => $this->feederDosen['nama_dosen'] ?? 'unknown',
                'error' => $e->getMessage(),
                'batch_id' => $actualBatchId,
            ]);

            // Don't re-throw - let batch continue with other records
        } finally {
            // Always update batch progress (success or fail)
            if ($actualBatchId) {
                $this->updateBatchProgress($actualBatchId, $success, $errorMessage);
            }
        }
    }

    /**
     * Parse tanggal dari format API Feeder (dd-mm-yyyy) ke Carbon
     */
    protected function parseDate(?string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Format dari API: "29-05-1975"
            return Carbon::createFromFormat('d-m-Y', $dateString);
        } catch (Exception $e) {
            Log::warning('Failed to parse date', [
                'institusi_id' => $this->institusiId,
                'date_string' => $dateString,
                'error' => $e->getMessage(),
                'batch_id' => $this->batchIdValue,
            ]);

            return null;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(?\Throwable $exception): void
    {
        // Get the actual Laravel batch ID
        $actualBatchId = $this->batch()?->id;

        Log::error('SyncDosenRecordJob failed permanently', [
            'institusi_id' => $this->institusiId,
            'feeder_dosen_id' => $this->feederDosen['id_dosen'] ?? 'unknown',
            'batch_id' => $actualBatchId,
            'error' => $exception?->getMessage(),
        ]);

        // Update batch progress on permanent failure
        if ($actualBatchId) {
            $this->updateBatchProgress($actualBatchId, false, $exception?->getMessage() ?? 'Job failed permanently');
        }
    }
}
