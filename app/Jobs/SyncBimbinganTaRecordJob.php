<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Dosen;
use App\Models\Institusi;
use App\Models\LprAktivitasMahasiswa;
use App\Models\LprBimbinganTa;
use App\Models\Mahasiswa;
use App\Traits\TracksBatchProgress;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Worker job untuk memproses satu record bimbingan TA dari Feeder API.
 *
 * Job ini menangani sinkronisasi data bimbingan mahasiswa (tugas akhir/skripsi)
 * dengan dosen pembimbing dari endpoint GetMahasiswaBimbinganDosen.
 */
class SyncBimbinganTaRecordJob implements ShouldQueue
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
     * @param  array  $feederBimbingan  Data dari API GetMahasiswaBimbinganDosen
     */
    public function __construct(
        protected Institusi $institusi,
        protected array $feederBimbingan,
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
            if (
                empty($this->feederBimbingan['id_bimbing_mahasiswa']) ||
                empty($this->feederBimbingan['id_dosen']) ||
                empty($this->feederBimbingan['nama_dosen'])
            ) {
                Log::warning('Skipping bimbingan record due to missing required fields', [
                    'record' => $this->feederBimbingan,
                ]);

                return;
            }

            // Find related mahasiswa by checking the aktivitas
            $mahasiswa = $this->findMahasiswaFromAktivitas($this->feederBimbingan['id_aktivitas']);

            // Find dosen by feeder_id
            $dosen = null;
            if (! empty($this->feederBimbingan['id_dosen'])) {
                $dosen = Dosen::where('institusi_id', $this->institusi->id)
                    ->where('dosen_feeder_id', $this->feederBimbingan['id_dosen'])
                    ->first();
            }

            // Prepare bimbingan data
            $bimbinganData = [
                'institusi_id' => $this->institusi->id,
                'mahasiswa_id' => $mahasiswa?->id,
                'dosen_id' => $dosen?->id,
                'id_bimbing_mahasiswa' => $this->feederBimbingan['id_bimbing_mahasiswa'],
                'id_aktivitas' => $this->feederBimbingan['id_aktivitas'],
                'judul' => $this->feederBimbingan['judul'] ?? null,
                'id_kategori_kegiatan' => $this->feederBimbingan['id_kategori_kegiatan'] ?? null,
                'nama_kategori_kegiatan' => $this->feederBimbingan['nama_kategori_kegiatan'] ?? null,
                'id_dosen' => $this->feederBimbingan['id_dosen'],
                'nidn' => $this->feederBimbingan['nidn'] ?? null,
                'nuptk' => $this->feederBimbingan['nuptk'] ?? null,
                'nama_dosen' => $this->feederBimbingan['nama_dosen'],
                'pembimbing_ke' => (string) $this->feederBimbingan['pembimbing_ke'],
            ];

            // Check if bimbingan already exists
            $existingBimbingan = LprBimbinganTa::where('institusi_id', $this->institusi->id)
                ->where('id_bimbing_mahasiswa', $this->feederBimbingan['id_bimbing_mahasiswa'])
                ->first();

            if ($existingBimbingan) {
                // Update if there are changes
                if ($this->hasChanges($existingBimbingan, $bimbinganData)) {
                    $existingBimbingan->update($bimbinganData);

                    Log::debug('Bimbingan TA updated', [
                        'id_bimbing_mahasiswa' => $this->feederBimbingan['id_bimbing_mahasiswa'],
                        'mahasiswa' => $mahasiswa?->nama ?? 'N/A',
                        'dosen' => $this->feederBimbingan['nama_dosen'],
                    ]);

                    if ($actualBatchId) {
                        $this->updateBatchProgress($actualBatchId, true);
                    }
                }
            } else {
                // Create new bimbingan
                LprBimbinganTa::create($bimbinganData);

                Log::debug('Bimbingan TA created', [
                    'id_bimbing_mahasiswa' => $this->feederBimbingan['id_bimbing_mahasiswa'],
                    'mahasiswa' => $mahasiswa?->nama ?? 'N/A',
                    'dosen' => $this->feederBimbingan['nama_dosen'],
                ]);

                if ($actualBatchId) {
                    $this->updateBatchProgress($actualBatchId, true);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync bimbingan record', [
                'id_bimbing_mahasiswa' => $this->feederBimbingan['id_bimbing_mahasiswa'] ?? 'unknown',
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
     * Find mahasiswa from aktivitas (since the API doesn't directly provide mahasiswa info)
     *
     * Strategy:
     * 1. Find through lpr_aktivitas_mahasiswa table (if exists and synced)
     * 2. Find through existing bimbingan records (if any)
     * 3. Return null if not found (will store bimbingan data without mahasiswa relationship)
     */
    protected function findMahasiswaFromAktivitas(string $aktivitasFeederId): ?Mahasiswa
    {
        // Strategy 1: Find through lpr_aktivitas_mahasiswa table
        $aktivitasMahasiswa = LprAktivitasMahasiswa::where('institusi_id', $this->institusi->id)
            ->where('aktivitas_feeder_id', $aktivitasFeederId)
            ->first();

        if ($aktivitasMahasiswa) {
            return Mahasiswa::where('institusi_id', $this->institusi->id)
                ->where('id', $aktivitasMahasiswa->mahasiswa_id)
                ->first();
        }

        // Strategy 2: Find through existing bimbingan records
        $existingBimbingan = LprBimbinganTa::where('institusi_id', $this->institusi->id)
            ->where('id_aktivitas', $aktivitasFeederId)
            ->first();

        if ($existingBimbingan && $existingBimbingan->mahasiswa) {
            return $existingBimbingan->mahasiswa;
        }

        // Strategy 3: Could not find mahasiswa
        // This might require a separate API call to get mahasiswa info from aktivitas
        // For now, we'll allow null and just store the bimbingan data without mahasiswa relationship
        Log::debug('Could not find mahasiswa for aktivitas', [
            'aktivitas_feeder_id' => $aktivitasFeederId,
        ]);

        return null;
    }

    /**
     * Check if bimbingan data has changes
     */
    protected function hasChanges(LprBimbinganTa $existing, array $newData): bool
    {
        $fieldsToCheck = [
            'mahasiswa_id',
            'dosen_id',
            'id_aktivitas',
            'judul',
            'id_kategori_kegiatan',
            'nama_kategori_kegiatan',
            'id_dosen',
            'nidn',
            'nuptk',
            'nama_dosen',
            'pembimbing_ke',
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
