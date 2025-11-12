<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\LprPrestasiMahasiswa;
use App\Models\Mahasiswa;
use App\Traits\TracksBatchProgress;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Worker job untuk memproses satu record prestasi mahasiswa dari Feeder API.
 *
 * Job ini menangani sinkronisasi data prestasi mahasiswa
 * dari endpoint GetListPrestasiMahasiswa.
 */
class SyncPrestasiMahasiswaRecordJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels, TracksBatchProgress;

    /**
     * Job timeout in seconds (2 minutes per record)
     */
    public int $timeout = 120;

    /**
     * Number of retries if job fails
     */
    public int $tries = 3;

    /**
     * @param  array  $feederPrestasi  Data dari API GetListPrestasiMahasiswa
     */
    public function __construct(
        protected Institusi $institusi,
        protected array $feederPrestasi,
        protected string $syncProcessId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Get the actual Laravel batch ID
        $actualBatchId = $this->batch()?->id;

        try {
            // Validate required fields
            if (empty($this->feederPrestasi['id_prestasi']) || empty($this->feederPrestasi['id_mahasiswa'])) {
                Log::warning('Missing required fields in prestasi mahasiswa data', [
                    'data' => $this->feederPrestasi,
                ]);

                return;
            }

            // Find mahasiswa by feeder ID (using mahasiswa_feeder_id for person-level lookup)
            $mahasiswa = Mahasiswa::where('mahasiswa_feeder_id', $this->feederPrestasi['id_mahasiswa'])->first();

            if (! $mahasiswa) {
                Log::warning('Mahasiswa not found for prestasi record', [
                    'id_mahasiswa_feeder' => $this->feederPrestasi['id_mahasiswa'],
                    'nama_mahasiswa' => $this->feederPrestasi['nama_mahasiswa'] ?? 'N/A',
                    'prestasi_feeder_id' => $this->feederPrestasi['id_prestasi'],
                ]);

                return;
            }

            // Check if prestasi already exists
            $existingRecord = LprPrestasiMahasiswa::where([
                'institusi_id' => $this->institusi->id,
                'id_prestasi' => $this->feederPrestasi['id_prestasi'],
            ])->first();

            // Prepare record data
            $recordData = [
                'institusi_id' => $this->institusi->id,
                'mahasiswa_id' => $mahasiswa->id,
                'id_mahasiswa' => $this->feederPrestasi['id_mahasiswa'],
                'id_prestasi' => $this->feederPrestasi['id_prestasi'],
                'jenis_prestasi' => $this->feederPrestasi['jenis_prestasi'] ?? null,
                'registrasi_feeder_id' => $this->feederPrestasi['id_registrasi_mahasiswa'] ?? null,
                'nim' => $mahasiswa->nim,
                'nama_mahasiswa' => $this->feederPrestasi['nama_mahasiswa'] ?? $mahasiswa->nama,
                'nama_prestasi' => $this->feederPrestasi['nama_prestasi'] ?? '',
                'peringkat' => ! empty($this->feederPrestasi['peringkat'])
                    ? (int) $this->feederPrestasi['peringkat']
                    : null,
                'tingkat_prestasi' => $this->feederPrestasi['nama_tingkat_prestasi'] ?? '',
                'tahun_prestasi' => ! empty($this->feederPrestasi['tahun_prestasi'])
                    ? (int) $this->feederPrestasi['tahun_prestasi']
                    : null,
                'penyelenggara' => $this->feederPrestasi['penyelenggara'] ?? '',
            ];

            if ($existingRecord) {
                // Update if there are changes
                if ($this->hasChanges($existingRecord, $recordData)) {
                    $existingRecord->update($recordData);

                    Log::debug('Prestasi mahasiswa updated', [
                        'id' => $existingRecord->id,
                        'id_prestasi' => $this->feederPrestasi['id_prestasi'],
                        'mahasiswa' => $mahasiswa->nama,
                    ]);

                    if ($actualBatchId) {
                        $this->updateBatchProgress($actualBatchId, true);
                    }
                }
            } else {
                // Create new record
                LprPrestasiMahasiswa::create($recordData);

                Log::debug('Prestasi mahasiswa created', [
                    'id_prestasi' => $this->feederPrestasi['id_prestasi'],
                    'mahasiswa' => $mahasiswa->nama,
                ]);

                if ($actualBatchId) {
                    $this->updateBatchProgress($actualBatchId, true);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync prestasi mahasiswa record', [
                'id_prestasi' => $this->feederPrestasi['id_prestasi'] ?? 'unknown',
                'id_mahasiswa' => $this->feederPrestasi['id_mahasiswa'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($actualBatchId) {
                $this->updateBatchProgress($actualBatchId, false, $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Check if prestasi data has changes
     */
    protected function hasChanges(LprPrestasiMahasiswa $existing, array $newData): bool
    {
        $fieldsToCheck = [
            'mahasiswa_id',
            'id_mahasiswa',
            'jenis_prestasi',
            'registrasi_feeder_id',
            'nim',
            'nama_mahasiswa',
            'nama_prestasi',
            'peringkat',
            'tingkat_prestasi',
            'tahun_prestasi',
            'penyelenggara',
        ];

        foreach ($fieldsToCheck as $field) {
            if ($newData[$field] != $existing->$field) {
                return true;
            }
        }

        return false;
    }

    /**
     * This is called automatically by Laravel when the job completes
     */
    public function __destruct()
    {
        // Progress tracking is already handled in the handle() method
    }
}
