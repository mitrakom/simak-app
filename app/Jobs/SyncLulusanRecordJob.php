<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\LprLulusan;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Traits\TracksBatchProgress;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLulusanRecordJob implements ShouldQueue
{
    use Batchable, Queueable, TracksBatchProgress;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederLulusan,
        protected ?string $batchIdValue = null
    ) {}

    /**
     * Execute the job - Process single lulusan record
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
            Log::error('Institusi not found for SyncLulusanRecordJob', [
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
                if (empty($this->feederLulusan['id_registrasi_mahasiswa']) || empty($this->feederLulusan['id_mahasiswa'])) {
                    Log::warning('Skipping lulusan - missing required fields', [
                        'registrasi_feeder_id' => $this->feederLulusan['id_registrasi_mahasiswa'] ?? 'missing',
                        'mahasiswa_feeder_id' => $this->feederLulusan['id_mahasiswa'] ?? 'missing',
                        'nim' => $this->feederLulusan['nim'] ?? 'unknown',
                    ]);

                    return;
                }

                // Find related mahasiswa (using mahasiswa_feeder_id for person-level lookup)
                $mahasiswa = Mahasiswa::where('institusi_id', $institusi->id)
                    ->where('mahasiswa_feeder_id', $this->feederLulusan['id_mahasiswa'])
                    ->first();

                if (! $mahasiswa) {
                    Log::warning('Mahasiswa not found for lulusan', [
                        'id_mahasiswa' => $this->feederLulusan['id_mahasiswa'],
                        'nim' => $this->feederLulusan['nim'] ?? 'unknown',
                        'nama_mahasiswa' => $this->feederLulusan['nama_mahasiswa'] ?? 'unknown',
                    ]);

                    return;
                }

                // Find related prodi
                $prodi = null;
                if (! empty($this->feederLulusan['id_prodi'])) {
                    $prodi = Prodi::where('institusi_id', $institusi->id)
                        ->where('prodi_feeder_id', $this->feederLulusan['id_prodi'])
                        ->first();
                }

                if (! $prodi) {
                    Log::warning('Prodi not found for lulusan', [
                        'id_prodi' => $this->feederLulusan['id_prodi'] ?? 'missing',
                        'nama_program_studi' => $this->feederLulusan['nama_program_studi'] ?? 'unknown',
                        'nim' => $this->feederLulusan['nim'] ?? 'unknown',
                    ]);

                    return;
                }

                // Calculate masa studi in months
                $masaStudiBulan = null;
                if (! empty($this->feederLulusan['tgl_masuk_sp']) && ! empty($this->feederLulusan['tgl_keluar'])) {
                    try {
                        $tglMasuk = Carbon::createFromFormat('d-m-Y', $this->feederLulusan['tgl_masuk_sp']);
                        $tglKeluar = Carbon::createFromFormat('d-m-Y', $this->feederLulusan['tgl_keluar']);
                        $masaStudiBulan = $tglMasuk->diffInMonths($tglKeluar);
                    } catch (Exception $e) {
                        Log::debug('Invalid date format for masa studi', [
                            'tgl_masuk_sp' => $this->feederLulusan['tgl_masuk_sp'],
                            'tgl_keluar' => $this->feederLulusan['tgl_keluar'],
                        ]);
                    }
                }

                // Parse dates
                $tanggalKeluar = $this->parseDate($this->feederLulusan['tanggal_keluar'] ?? null);
                $tanggalSkYudisium = $this->parseDate($this->feederLulusan['tgl_sk_yudisium'] ?? null);

                // Prepare lulusan data
                $lulusanData = [
                    'institusi_id' => $institusi->id,
                    'mahasiswa_id' => $mahasiswa->id,
                    'prodi_id' => $prodi->id,
                    'mahasiswa_feeder_id' => $this->feederLulusan['id_mahasiswa'],
                    'registrasi_feeder_id' => $this->feederLulusan['id_registrasi_mahasiswa'],
                    'nim' => $this->feederLulusan['nim'] ?? '',
                    'nama_mahasiswa' => $this->feederLulusan['nama_mahasiswa'] ?? '',
                    'nama_prodi' => $this->feederLulusan['nama_program_studi'] ?? '',
                    'angkatan' => $this->feederLulusan['angkatan'] ?? null,
                    'status_keluar' => $this->feederLulusan['nama_jenis_keluar'] ?? '',
                    'tanggal_keluar' => $tanggalKeluar,
                    'ipk_lulusan' => ! empty($this->feederLulusan['ipk']) && $this->feederLulusan['ipk'] > 0 ? $this->feederLulusan['ipk'] : null,
                    'masa_studi_bulan' => $masaStudiBulan,
                    'nomor_ijazah' => $this->feederLulusan['no_seri_ijazah'] ?? null,
                    'nomor_sk_yudisium' => $this->feederLulusan['sk_yudisium'] ?? null,
                    'tanggal_sk_yudisium' => $tanggalSkYudisium,
                    'judul_skripsi' => $this->feederLulusan['judul_skripsi'] ?? null,
                    'periode_keluar' => $this->feederLulusan['id_periode_keluar'] ?? null,
                    'updated_at' => now(),
                ];

                // Check if lulusan already exists
                $existingLulusan = LprLulusan::where('institusi_id', $institusi->id)
                    ->where('registrasi_feeder_id', $this->feederLulusan['id_registrasi_mahasiswa'])
                    ->first();

                if ($existingLulusan) {
                    // Update if there are changes
                    if ($this->hasChanges($existingLulusan, $lulusanData)) {
                        $existingLulusan->update($lulusanData);
                        Log::debug('Lulusan updated', [
                            'nim' => $this->feederLulusan['nim'] ?? 'unknown',
                            'nama' => $this->feederLulusan['nama_mahasiswa'] ?? 'unknown',
                        ]);
                    }
                } else {
                    // Create new lulusan
                    $lulusanData['created_at'] = now();
                    LprLulusan::create($lulusanData);
                    Log::debug('Lulusan created', [
                        'nim' => $this->feederLulusan['nim'] ?? 'unknown',
                        'nama' => $this->feederLulusan['nama_mahasiswa'] ?? 'unknown',
                    ]);
                }

                $success = true;
            });
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Failed to sync lulusan record', [
                'registrasi_feeder_id' => $this->feederLulusan['id_registrasi_mahasiswa'] ?? 'unknown',
                'nim' => $this->feederLulusan['nim'] ?? 'unknown',
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
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $actualBatchId = $this->batch()?->id;

        Log::error('SyncLulusanRecordJob permanently failed', [
            'institusi_id' => $this->institusiId,
            'registrasi_feeder_id' => $this->feederLulusan['id_registrasi_mahasiswa'] ?? 'unknown',
            'batch_id' => $actualBatchId,
            'error' => $exception->getMessage(),
        ]);

        if ($actualBatchId) {
            $this->updateBatchProgress($actualBatchId, false, $exception->getMessage());
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    private function hasChanges(LprLulusan $existing, array $newData): bool
    {
        $fieldsToCheck = [
            'mahasiswa_id',
            'prodi_id',
            'nim',
            'nama_mahasiswa',
            'nama_prodi',
            'angkatan',
            'status_keluar',
            'tanggal_keluar',
            'ipk_lulusan',
            'masa_studi_bulan',
            'nomor_ijazah',
            'nomor_sk_yudisium',
            'tanggal_sk_yudisium',
            'judul_skripsi',
            'periode_keluar',
        ];

        foreach ($fieldsToCheck as $field) {
            if ($newData[$field] != $existing->$field) {
                return true;
            }
        }

        return false;
    }
}
