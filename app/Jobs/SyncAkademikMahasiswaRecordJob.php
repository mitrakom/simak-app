<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Institusi;
use App\Models\LprAkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Services\FeederClient;
use App\Traits\TracksBatchProgress;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAkademikMahasiswaRecordJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels, TracksBatchProgress;

    /**
     * Create a new job instance.
     *
     * @param  int  $institusiId  Institusi ID (multitenancy)
     * @param  array  $feederRecord  Data from Feeder API
     * @param  string  $syncProcessId  Unique sync process ID
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederRecord,
        protected string $syncProcessId
    ) {}

    public function handle(FeederClient $feederClient): void
    {
        // If batch cancelled, skip
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Get the actual Laravel batch ID
        $actualBatchId = $this->batch()?->id;

        $success = false;
        $errorMessage = null;

        try {
            DB::transaction(function () use (&$success) {
                // Find mahasiswa by NIM
                $nim = $this->feederRecord['nim'] ?? null;

                if (! $nim) {
                    throw new Exception('Missing nim in feeder record');
                }

                $mahasiswa = Mahasiswa::where('institusi_id', $this->institusiId)
                    ->where('nim', $nim)
                    ->first();

                if (! $mahasiswa) {
                    // Mahasiswa not found; skip and log
                    Log::warning('Mahasiswa not found for akademik record', [
                        'institusi_id' => $this->institusiId,
                        'nim' => $nim,
                        'feeder_registrasi' => $this->feederRecord['id_registrasi_mahasiswa'] ?? null,
                        'sync_process_id' => $this->syncProcessId,
                    ]);

                    return;
                }

                // Direct API-to-DB mapping
                $data = [
                    'institusi_id' => $this->institusiId,
                    'mahasiswa_id' => $mahasiswa->id,
                    'mahasiswa_feeder_id' => $mahasiswa->mahasiswa_feeder_id,
                    'registrasi_feeder_id' => $this->feederRecord['id_registrasi_mahasiswa'] ?? null,
                    'nim' => $nim,
                    'nama_mahasiswa' => $this->feederRecord['nama_mahasiswa'] ?? null,
                    'angkatan' => $this->feederRecord['angkatan'] ?? null,
                    'nama_program_studi' => $this->feederRecord['nama_program_studi'] ?? null,
                    'id_semester' => $this->feederRecord['id_semester'] ?? null,
                    'nama_semester' => $this->feederRecord['nama_semester'] ?? null,
                    'id_status_mahasiswa' => $this->feederRecord['id_status_mahasiswa'] ?? null,
                    'nama_status_mahasiswa' => $this->feederRecord['nama_status_mahasiswa'] ?? null,
                    'ips' => $this->parseFloat($this->feederRecord['ips'] ?? null),
                    'ipk' => $this->parseFloat($this->feederRecord['ipk'] ?? null),
                    'sks_semester' => $this->parseInt($this->feederRecord['sks_semester'] ?? null),
                    'sks_total' => $this->parseInt($this->feederRecord['sks_total'] ?? null),
                    'biaya_kuliah_smt' => $this->parseFloat($this->feederRecord['biaya_kuliah_smt'] ?? null),
                    'id_pembiayaan' => $this->feederRecord['id_pembiayaan'] ?? null,
                    'updated_at' => now(),
                ];

                // Check existing record by registrasi_feeder_id + id_semester
                $existing = LprAkademikMahasiswa::where('institusi_id', $this->institusiId)
                    ->where('registrasi_feeder_id', $data['registrasi_feeder_id'])
                    ->where('id_semester', $data['id_semester'])
                    ->first();

                if ($existing) {
                    // Update if changed
                    $changed = false;
                    foreach ($data as $k => $v) {
                        if ($k === 'updated_at') {
                            continue;
                        }
                        if ($existing->{$k} != $v) {
                            $changed = true;
                            break;
                        }
                    }

                    if ($changed) {
                        $existing->update($data);
                        Log::debug('Academic record updated', [
                            'nim' => $nim,
                            'id_semester' => $data['id_semester'],
                        ]);
                    } else {
                        Log::debug('Academic record unchanged', [
                            'nim' => $nim,
                            'id_semester' => $data['id_semester'],
                        ]);
                    }
                } else {
                    // Create new record
                    $data['created_at'] = now();
                    LprAkademikMahasiswa::create($data);

                    Log::debug('Academic record created', [
                        'nim' => $nim,
                        'id_semester' => $data['id_semester'],
                    ]);
                }

                $success = true;
            });
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Failed to sync academic record', [
                'nim' => $this->feederRecord['nim'] ?? null,
                'semester' => $this->feederRecord['id_semester'] ?? null,
                'error' => $errorMessage,
                'batch_id' => $actualBatchId,
            ]);
        } finally {
            if ($actualBatchId) {
                $this->updateBatchProgress($actualBatchId, $success, $errorMessage);
            }
        }
    }

    private function parseFloat($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function parseInt($value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
