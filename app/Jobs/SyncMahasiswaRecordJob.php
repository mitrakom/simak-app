<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\FeederClient;
use App\Traits\TracksBatchProgress;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMahasiswaRecordJob implements ShouldQueue
{
    use Batchable, Queueable, TracksBatchProgress;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederMahasiswa,
        protected ?string $batchIdValue = null,
        protected bool $fetchBiodataDetail = false
    ) {}

    /**
     * Execute the job - Process single mahasiswa record
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
            Log::error('Institusi not found for SyncMahasiswaRecordJob', [
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
                // Get prodi_id from id_prodi
                $prodi = Prodi::where('institusi_id', $institusi->id)
                    ->where('id_prodi', $this->feederMahasiswa['id_prodi'])
                    ->first();

                if (! $prodi) {
                    Log::warning('Prodi not found for mahasiswa, skipping', [
                        'institusi_slug' => $institusi->slug,
                        'institusi_id' => $institusi->id,
                        'id_prodi' => $this->feederMahasiswa['id_prodi'],
                        'nama_prodi' => $this->feederMahasiswa['nama_program_studi'] ?? 'unknown',
                        'id_mahasiswa' => $this->feederMahasiswa['id_mahasiswa'] ?? 'unknown',
                        'nim' => $this->feederMahasiswa['nim'] ?? 'unknown',
                        'nama_mahasiswa' => $this->feederMahasiswa['nama_mahasiswa'] ?? 'unknown',
                        'batch_id' => $actualBatchId,
                    ]);
                    $success = false; // Mark as failed since prodi not found

                    return;
                }

                // Extract angkatan from id_periode (first 4 characters)
                $angkatan = substr($this->feederMahasiswa['id_periode'] ?? '', 0, 4);

                // Direct API-to-DB mapping (no translation needed)
                $mahasiswaData = [
                    'institusi_id' => $institusi->id,
                    'prodi_id' => $prodi->id,
                    'mahasiswa_feeder_id' => $this->feederMahasiswa['id_mahasiswa'],
                    'registrasi_feeder_id' => $this->feederMahasiswa['id_registrasi_mahasiswa'],
                    'id_sms' => $this->feederMahasiswa['id_sms'] ?: null,
                    'nim' => $this->feederMahasiswa['nim'],
                    'nipd' => $this->feederMahasiswa['nipd'] ?: null,
                    'nama_mahasiswa' => $this->feederMahasiswa['nama_mahasiswa'],
                    'angkatan' => $angkatan ?: null,
                    'jenis_kelamin' => $this->feederMahasiswa['jenis_kelamin'] ?: null,
                    'tanggal_lahir' => $this->parseDate($this->feederMahasiswa['tanggal_lahir'] ?? null),
                    'id_agama' => $this->feederMahasiswa['id_agama'] ?: null,
                    'nama_agama' => $this->feederMahasiswa['nama_agama'] ?: null,
                    'id_status_mahasiswa' => $this->feederMahasiswa['id_status_mahasiswa'] ?: null,
                    'nama_status_mahasiswa' => $this->feederMahasiswa['nama_status_mahasiswa'] ?: null,
                    'id_periode' => $this->feederMahasiswa['id_periode'] ?: null,
                    'nama_periode_masuk' => $this->feederMahasiswa['nama_periode_masuk'] ?: null,
                    'ipk' => $this->parseDecimal($this->feederMahasiswa['ipk'] ?? null),
                    'total_sks' => $this->parseInt($this->feederMahasiswa['total_sks'] ?? null),
                ];

                // CRITICAL CHANGE: Lookup by registrasi_feeder_id (PRIMARY identifier per enrollment)
                // This fixes the duplicate issue where 1 person has multiple enrollments (different NIMs)
                $existingMahasiswa = Mahasiswa::where('institusi_id', $institusi->id)
                    ->where('registrasi_feeder_id', $this->feederMahasiswa['id_registrasi_mahasiswa'])
                    ->first();

                if ($existingMahasiswa) {
                    // Update existing mahasiswa if changed
                    $wasUpdated = $this->updateMahasiswaIfChanged($existingMahasiswa, $mahasiswaData, $institusi->id);

                    // Fetch biodata detail if enabled and not yet fetched
                    if ($this->fetchBiodataDetail && ! $existingMahasiswa->has_biodata_detail) {
                        $this->fetchAndUpdateBiodata($existingMahasiswa, $institusi);
                    }
                } else {
                    // Create new mahasiswa
                    $newMahasiswa = Mahasiswa::create($mahasiswaData);

                    Log::debug('Mahasiswa created', [
                        'institusi_id' => $institusi->id,
                        'mahasiswa_feeder_id' => $this->feederMahasiswa['id_mahasiswa'],
                        'registrasi_feeder_id' => $this->feederMahasiswa['id_registrasi_mahasiswa'],
                        'nim' => $mahasiswaData['nim'],
                        'nama_mahasiswa' => $mahasiswaData['nama_mahasiswa'],
                        'batch_id' => $actualBatchId,
                    ]);

                    // Fetch biodata detail if enabled
                    if ($this->fetchBiodataDetail) {
                        $this->fetchAndUpdateBiodata($newMahasiswa, $institusi);
                    }
                }

                $success = true;
            });
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            Log::error('Failed to sync individual mahasiswa', [
                'institusi_id' => $institusi->id,
                'feeder_mahasiswa_id' => $this->feederMahasiswa['id_mahasiswa'] ?? 'unknown',
                'nim' => $this->feederMahasiswa['nim'] ?? 'unknown',
                'nama' => $this->feederMahasiswa['nama_mahasiswa'] ?? 'unknown',
                'id_prodi' => $this->feederMahasiswa['id_prodi'] ?? 'unknown',
                'nama_prodi' => $this->feederMahasiswa['nama_program_studi'] ?? 'unknown',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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
     * Update mahasiswa if data has changed
     */
    protected function updateMahasiswaIfChanged(Mahasiswa $mahasiswa, array $newData, int $institusiId): bool
    {
        $hasChanges = false;

        foreach ($newData as $key => $value) {
            if ($key === 'institusi_id' || $key === 'mahasiswa_feeder_id' || $key === 'registrasi_feeder_id') {
                continue; // Skip identifier fields
            }

            if ($value != $mahasiswa->$key) {
                $hasChanges = true;
                break;
            }
        }

        if ($hasChanges) {
            $mahasiswa->update($newData);

            Log::debug('Mahasiswa updated', [
                'institusi_id' => $institusiId,
                'mahasiswa_feeder_id' => $mahasiswa->mahasiswa_feeder_id,
                'registrasi_feeder_id' => $mahasiswa->registrasi_feeder_id,
                'nim' => $newData['nim'],
                'nama_mahasiswa' => $newData['nama_mahasiswa'],
                'batch_id' => $this->batchIdValue,
            ]);

            return true;
        }

        Log::debug('Mahasiswa unchanged', [
            'institusi_id' => $institusiId,
            'mahasiswa_feeder_id' => $mahasiswa->mahasiswa_feeder_id,
            'registrasi_feeder_id' => $mahasiswa->registrasi_feeder_id,
            'nim' => $mahasiswa->nim,
            'batch_id' => $this->batchIdValue,
        ]);

        return false;
    }

    /**
     * Fetch and update biodata detail from GetBiodataMahasiswa endpoint
     */
    protected function fetchAndUpdateBiodata(Mahasiswa $mahasiswa, Institusi $institusi): void
    {
        try {
            $feederClient = new FeederClient;
            $feederClient->setInstitusi($institusi);

            $biodataResponse = $feederClient->getBiodataMahasiswa(['id_mahasiswa' => $mahasiswa->mahasiswa_feeder_id]);

            if (! $biodataResponse || ($biodataResponse['error_code'] ?? 0) != 0) {
                Log::warning('Failed to fetch biodata for mahasiswa', [
                    'institusi_id' => $institusi->id,
                    'mahasiswa_id' => $mahasiswa->mahasiswa_feeder_id,
                    'nim' => $mahasiswa->nim,
                    'batch_id' => $this->batchIdValue,
                ]);

                return;
            }

            $biodataData = $biodataResponse['data'][0] ?? null;
            if (! $biodataData) {
                return;
            }

            // Update with biodata detail
            $mahasiswa->update([
                'tempat_lahir' => $biodataData['tempat_lahir'] ?: null,
                'nik' => $biodataData['nik'] ?: null,
                'nisn' => $biodataData['nisn'] ?: null,
                'email' => $biodataData['email'] ?: null,
                'handphone' => $biodataData['handphone'] ?: null,
                'nama_ibu_kandung' => $biodataData['nama_ibu_kandung'] ?: null,
                'has_biodata_detail' => true,
            ]);

            Log::debug('Biodata fetched and updated', [
                'institusi_id' => $institusi->id,
                'mahasiswa_id' => $mahasiswa->mahasiswa_feeder_id,
                'nim' => $mahasiswa->nim,
                'batch_id' => $this->batchIdValue,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch biodata for mahasiswa', [
                'institusi_id' => $institusi->id,
                'mahasiswa_id' => $mahasiswa->feeder_id,
                'nim' => $mahasiswa->nim,
                'error' => $e->getMessage(),
                'batch_id' => $this->batchIdValue,
            ]);
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
     * Parse decimal value safely
     */
    protected function parseDecimal(?string $value): ?float
    {
        if (empty($value) || $value === '0.00') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Parse integer value safely
     */
    protected function parseInt(?string $value): ?int
    {
        if (empty($value) || $value === '0') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Handle job failure
     */
    public function failed(?\Throwable $exception): void
    {
        // Get the actual Laravel batch ID
        $actualBatchId = $this->batch()?->id;

        Log::error('SyncMahasiswaRecordJob failed permanently', [
            'institusi_id' => $this->institusiId,
            'feeder_mahasiswa_id' => $this->feederMahasiswa['id_mahasiswa'] ?? 'unknown',
            'batch_id' => $actualBatchId,
            'error' => $exception?->getMessage(),
        ]);

        // Update batch progress on permanent failure
        if ($actualBatchId) {
            $this->updateBatchProgress($actualBatchId, false, $exception?->getMessage() ?? 'Job failed permanently');
        }
    }
}
