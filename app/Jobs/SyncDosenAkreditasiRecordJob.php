<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Dosen;
use App\Models\Institusi;
use App\Models\LprDosenAkreditasi;
use App\Traits\TracksBatchProgress;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Worker job untuk memproses satu record dosen akreditasi dari Feeder API.
 *
 * Job ini menangani 3 jenis data:
 * 1. Data Pendidikan (S1/S2/S3/Profesi)
 * 2. Data Jabatan Fungsional
 * 3. Data Sertifikasi Dosen
 *
 * Semua data di-aggregate ke satu record LprDosenAkreditasi per dosen.
 */
class SyncDosenAkreditasiRecordJob implements ShouldQueue
{
    use Batchable, Queueable, TracksBatchProgress;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederRecord,
        protected string $recordType,
        protected ?string $batchIdValue = null
    ) {}

    /**
     * Execute the job - Process single dosen akreditasi record
     */
    public function handle(): void
    {
        // Check if batch has been cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Get the actual Laravel batch ID
        $actualBatchId = $this->batch()?->id;

        // Get institusi from ID
        $institusi = Institusi::find($this->institusiId);

        if (! $institusi) {
            Log::error('Institusi not found for SyncDosenAkreditasiRecordJob', [
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
            DB::transaction(function () use ($institusi, &$success, &$errorMessage) {
                // Validate required fields
                if (empty($this->feederRecord['id_dosen']) || empty($this->feederRecord['nama_dosen'])) {
                    Log::warning("Skipping {$this->recordType} record due to missing required fields", [
                        'record_type' => $this->recordType,
                        'record' => $this->feederRecord,
                    ]);

                    return;
                }

                // Find or create dosen akreditasi record
                $dosenAkreditasi = LprDosenAkreditasi::firstOrNew([
                    'institusi_id' => $institusi->id,
                    'dosen_feeder_id' => $this->feederRecord['id_dosen'],
                ]);

                // Set basic dosen data if new record
                if (! $dosenAkreditasi->exists) {
                    $dosenAkreditasi->nidn = $this->feederRecord['nidn'] ?? '';
                    $dosenAkreditasi->nama_dosen = $this->feederRecord['nama_dosen'];

                    // Try to link with existing Dosen record
                    $dosen = Dosen::where('institusi_id', $institusi->id)
                        ->where('feeder_id', $this->feederRecord['id_dosen'])
                        ->first();

                    if ($dosen) {
                        $dosenAkreditasi->dosen_id = $dosen->id;
                    }
                }

                // Process based on record type
                $hasChanges = match ($this->recordType) {
                    'pendidikan' => $this->processPendidikanData($dosenAkreditasi),
                    'fungsional' => $this->processJabatanFungsionalData($dosenAkreditasi),
                    'sertifikasi' => $this->processSertifikasiData($dosenAkreditasi),
                    default => false
                };

                // Save if new or has changes
                if (! $dosenAkreditasi->exists || $hasChanges) {
                    $dosenAkreditasi->save();

                    Log::debug("Dosen akreditasi {$this->recordType} synced", [
                        'dosen_feeder_id' => $this->feederRecord['id_dosen'],
                        'nama_dosen' => $this->feederRecord['nama_dosen'],
                        'record_type' => $this->recordType,
                        'action' => $dosenAkreditasi->wasRecentlyCreated ? 'created' : 'updated',
                    ]);
                }

                // Update jenjang pendidikan tertinggi if pendidikan record
                if ($this->recordType === 'pendidikan') {
                    $this->updateJenjangPendidikanTertinggi($dosenAkreditasi);
                }

                $success = true;
            });
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error("Failed to sync {$this->recordType} record", [
                'dosen_feeder_id' => $this->feederRecord['id_dosen'] ?? 'unknown',
                'record_type' => $this->recordType,
                'error' => $errorMessage,
                'batch_id' => $actualBatchId,
            ]);
        }

        // Update batch progress
        if ($actualBatchId) {
            $this->updateBatchProgress($actualBatchId, $success, $errorMessage);
        }
    }

    /**
     * Process pendidikan data and update appropriate fields based on jenjang
     */
    protected function processPendidikanData(LprDosenAkreditasi $dosenAkreditasi): bool
    {
        $jenjang = $this->feederRecord['nama_jenjang_pendidikan'] ?? '';
        $bidangStudi = $this->feederRecord['nama_bidang_studi'] ?? '';
        $perguruanTinggi = $this->feederRecord['nama_perguruan_tinggi'] ?? '';
        $tahunLulus = $this->feederRecord['tahun_lulus'] ?? null;

        $hasChanges = false;

        // Map pendidikan based on jenjang
        switch (strtoupper($jenjang)) {
            case 'S1':
                if ($dosenAkreditasi->pendidikan_s1_bidang_studi !== $bidangStudi) {
                    $dosenAkreditasi->pendidikan_s1_bidang_studi = $bidangStudi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_s1_perguruan_tinggi !== $perguruanTinggi) {
                    $dosenAkreditasi->pendidikan_s1_perguruan_tinggi = $perguruanTinggi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_s1_tahun_lulus != $tahunLulus) {
                    $dosenAkreditasi->pendidikan_s1_tahun_lulus = $tahunLulus;
                    $hasChanges = true;
                }
                break;

            case 'S2':
                if ($dosenAkreditasi->pendidikan_s2_bidang_studi !== $bidangStudi) {
                    $dosenAkreditasi->pendidikan_s2_bidang_studi = $bidangStudi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_s2_perguruan_tinggi !== $perguruanTinggi) {
                    $dosenAkreditasi->pendidikan_s2_perguruan_tinggi = $perguruanTinggi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_s2_tahun_lulus != $tahunLulus) {
                    $dosenAkreditasi->pendidikan_s2_tahun_lulus = $tahunLulus;
                    $hasChanges = true;
                }
                break;

            case 'S3':
                if ($dosenAkreditasi->pendidikan_s3_bidang_studi !== $bidangStudi) {
                    $dosenAkreditasi->pendidikan_s3_bidang_studi = $bidangStudi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_s3_perguruan_tinggi !== $perguruanTinggi) {
                    $dosenAkreditasi->pendidikan_s3_perguruan_tinggi = $perguruanTinggi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_s3_tahun_lulus != $tahunLulus) {
                    $dosenAkreditasi->pendidikan_s3_tahun_lulus = $tahunLulus;
                    $hasChanges = true;
                }
                break;

            case 'PROFESI':
                if ($dosenAkreditasi->pendidikan_profesi_bidang_studi !== $bidangStudi) {
                    $dosenAkreditasi->pendidikan_profesi_bidang_studi = $bidangStudi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_profesi_perguruan_tinggi !== $perguruanTinggi) {
                    $dosenAkreditasi->pendidikan_profesi_perguruan_tinggi = $perguruanTinggi;
                    $hasChanges = true;
                }
                if ($dosenAkreditasi->pendidikan_profesi_tahun_lulus != $tahunLulus) {
                    $dosenAkreditasi->pendidikan_profesi_tahun_lulus = $tahunLulus;
                    $hasChanges = true;
                }
                break;

            default:
                Log::debug('Unknown education level', [
                    'jenjang' => $jenjang,
                    'dosen' => $this->feederRecord['nama_dosen'],
                ]);
                break;
        }

        return $hasChanges;
    }

    /**
     * Process jabatan fungsional data
     */
    protected function processJabatanFungsionalData(LprDosenAkreditasi $dosenAkreditasi): bool
    {
        $jabatanFungsional = $this->feederRecord['nama_jabatan_fungsional'] ?? '';
        $skJabatan = $this->feederRecord['sk_jabatan_fungsional'] ?? '';
        $tanggalSk = $this->parseDate($this->feederRecord['mulai_sk_jabatan'] ?? null);

        $hasChanges = false;

        if ($dosenAkreditasi->jabatan_fungsional_saat_ini !== $jabatanFungsional) {
            $dosenAkreditasi->jabatan_fungsional_saat_ini = $jabatanFungsional;
            $hasChanges = true;
        }

        if ($dosenAkreditasi->sk_jabatan_fungsional !== $skJabatan) {
            $dosenAkreditasi->sk_jabatan_fungsional = $skJabatan;
            $hasChanges = true;
        }

        if ($dosenAkreditasi->tanggal_sk_jabatan_fungsional != $tanggalSk) {
            $dosenAkreditasi->tanggal_sk_jabatan_fungsional = $tanggalSk;
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * Process sertifikasi data (only for Sertifikasi Dosen)
     */
    protected function processSertifikasiData(LprDosenAkreditasi $dosenAkreditasi): bool
    {
        // Only process Sertifikasi Dosen
        if (($this->feederRecord['nama_jenis_sertifikasi'] ?? '') !== 'Sertifikasi Dosen') {
            return false;
        }

        $tahunSertifikasi = $this->feederRecord['tahun_sertifikasi'] ?? null;
        $skSertifikasi = $this->feederRecord['sk_sertifikasi'] ?? '';
        $bidangSertifikasi = $this->feederRecord['nama_bidang_studi'] ?? '';

        $hasChanges = false;

        if (! $dosenAkreditasi->sudah_sertifikasi_dosen) {
            $dosenAkreditasi->sudah_sertifikasi_dosen = true;
            $hasChanges = true;
        }

        if ($dosenAkreditasi->tahun_sertifikasi_dosen != $tahunSertifikasi) {
            $dosenAkreditasi->tahun_sertifikasi_dosen = $tahunSertifikasi;
            $hasChanges = true;
        }

        if ($dosenAkreditasi->sk_sertifikasi_dosen !== $skSertifikasi) {
            $dosenAkreditasi->sk_sertifikasi_dosen = $skSertifikasi;
            $hasChanges = true;
        }

        if ($dosenAkreditasi->bidang_sertifikasi_dosen !== $bidangSertifikasi) {
            $dosenAkreditasi->bidang_sertifikasi_dosen = $bidangSertifikasi;
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * Parse date from DD-MM-YYYY format to Y-m-d format
     */
    protected function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Invalid date format', [
                'date' => $dateString,
                'dosen' => $this->feederRecord['nama_dosen'] ?? 'unknown',
            ]);

            return null;
        }
    }

    /**
     * Update jenjang pendidikan tertinggi based on pendidikan data
     */
    protected function updateJenjangPendidikanTertinggi(LprDosenAkreditasi $dosenAkreditasi): void
    {
        // Determine highest education level
        // Priority: S3 > Profesi > S2 > S1
        $jenjangTertinggi = null;

        if ($dosenAkreditasi->pendidikan_s3_bidang_studi) {
            $jenjangTertinggi = 'S3';
        } elseif ($dosenAkreditasi->pendidikan_profesi_bidang_studi) {
            $jenjangTertinggi = 'Profesi';
        } elseif ($dosenAkreditasi->pendidikan_s2_bidang_studi) {
            $jenjangTertinggi = 'S2';
        } elseif ($dosenAkreditasi->pendidikan_s1_bidang_studi) {
            $jenjangTertinggi = 'S1';
        }

        // Update if changed
        if ($dosenAkreditasi->jenjang_pendidikan_tertinggi !== $jenjangTertinggi) {
            $dosenAkreditasi->jenjang_pendidikan_tertinggi = $jenjangTertinggi;
            $dosenAkreditasi->save();

            Log::debug('Updated jenjang pendidikan tertinggi', [
                'dosen_feeder_id' => $dosenAkreditasi->dosen_feeder_id,
                'jenjang' => $jenjangTertinggi,
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $actualBatchId = $this->batch()?->id;

        Log::error('SyncDosenAkreditasiRecordJob permanently failed', [
            'institusi_id' => $this->institusiId,
            'dosen_feeder_id' => $this->feederRecord['id_dosen'] ?? 'unknown',
            'record_type' => $this->recordType,
            'batch_id' => $actualBatchId,
            'error' => $exception->getMessage(),
        ]);

        if ($actualBatchId) {
            $this->updateBatchProgress($actualBatchId, false, $exception->getMessage());
        }
    }
}
