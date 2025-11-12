<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Dosen;
use App\Models\Institusi;
use App\Models\LprAktivitasMahasiswa;
use App\Models\LprBimbinganTa;
use App\Services\FeederClient;
use App\Traits\TracksBatchProgress;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Worker job untuk memproses satu aktivitas mahasiswa dari Feeder API.
 *
 * Job ini menangani sinkronisasi data aktivitas mahasiswa dan anggotanya.
 * Untuk setiap aktivitas, akan mengambil data anggota dan menyimpan per mahasiswa.
 *
 * Note: Job ini berbeda dari yang lain karena perlu fetch data anggota
 * dari endpoint kedua untuk setiap aktivitas.
 */
class SyncAktivitasMahasiswaRecordJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels, TracksBatchProgress;

    /**
     * Job timeout in seconds (5 minutes per aktivitas - karena perlu fetch anggota)
     */
    public int $timeout = 300;

    /**
     * Number of retries if job fails
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  int  $institusiId  Institusi ID (multitenancy)
     * @param  array  $feederAktivitas  Data aktivitas dari API GetListAktivitasMahasiswa
     * @param  string  $syncProcessId  Unique sync process ID
     */
    public function __construct(
        protected int $institusiId,
        protected array $feederAktivitas,
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

        // Load institusi from ID
        $institusi = Institusi::findOrFail($this->institusiId);

        try {
            // Validate required fields
            if (empty($this->feederAktivitas['id_aktivitas'])) {
                Log::warning('Missing id_aktivitas in aktivitas data', [
                    'data' => $this->feederAktivitas,
                ]);

                return;
            }

            // Initialize FeederClient untuk fetch anggota
            $feederClient = new FeederClient;
            $feederClient->setInstitusi($institusi);

            // Fetch anggota untuk aktivitas ini
            $anggotaResponse = $feederClient->getListAnggotaAktivitasMahasiswa([
                'id_aktivitas' => $this->feederAktivitas['id_aktivitas'],
            ], '', 0, 0);

            if (! $anggotaResponse || ($anggotaResponse['error_code'] ?? 0) != 0) {
                Log::warning('Failed to fetch anggota for aktivitas', [
                    'id_aktivitas' => $this->feederAktivitas['id_aktivitas'],
                    'error' => $anggotaResponse['error_desc'] ?? 'Unknown error',
                ]);

                return;
            }

            $anggotaData = $anggotaResponse['data'] ?? [];

            Log::debug('Processing aktivitas with members', [
                'id_aktivitas' => $this->feederAktivitas['id_aktivitas'],
                'judul' => $this->feederAktivitas['judul'],
                'total_anggota' => count($anggotaData),
            ]);

            // Process setiap anggota
            $processedCount = 0;
            foreach ($anggotaData as $anggota) {
                if ($this->syncAktivitasMahasiswaRecord($feederClient, $anggota)) {
                    $processedCount++;
                }
            }

            if ($processedCount > 0 && $actualBatchId) {
                $this->updateBatchProgress($actualBatchId, true);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync aktivitas mahasiswa', [
                'id_aktivitas' => $this->feederAktivitas['id_aktivitas'] ?? 'unknown',
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
     * Sync individual aktivitas mahasiswa record for one member
     */
    protected function syncAktivitasMahasiswaRecord(FeederClient $feederClient, array $anggota): bool
    {
        try {
            // Validate required fields from API
            if (empty($anggota['id_anggota'])) {
                Log::warning('Missing id_anggota in anggota data', [
                    'anggota' => $anggota,
                ]);

                return false;
            }

            // UPSERT: Use updateOrCreate with unique constraint fields
            // NO foreign key validation - accept all data from API directly
            $aktivitas = LprAktivitasMahasiswa::updateOrCreate(
                [
                    // WHERE clause - matches unique constraint
                    'institusi_id' => $this->institusiId,
                    'id_anggota' => $anggota['id_anggota'], // UUID anggota (UNIQUE KEY)
                ],
                [
                    // SET clause - direct API field mapping (100% coverage)
                    // Column names match API response exactly

                    // From GetListAnggotaAktivitasMahasiswa
                    'id_aktivitas' => $this->feederAktivitas['id_aktivitas'],
                    'id_mahasiswa' => $anggota['id_registrasi_mahasiswa'],
                    'id_registrasi_mahasiswa' => $anggota['id_registrasi_mahasiswa'],
                    'nim' => $anggota['nim'],
                    'nama_mahasiswa' => $anggota['nama_mahasiswa'],
                    'judul' => $anggota['judul'],
                    'jenis_peran' => $anggota['jenis_peran'] ?? null,
                    'nama_jenis_peran' => $anggota['nama_jenis_peran'] ?? null,

                    // From GetListAktivitasMahasiswa
                    'id_jenis_aktivitas' => $this->feederAktivitas['id_jenis_aktivitas'] ?? null,
                    'nama_jenis_aktivitas' => $this->feederAktivitas['nama_jenis_aktivitas'] ?? null,
                    'lokasi' => $this->feederAktivitas['lokasi'] ?? null,
                    'id_semester' => $this->feederAktivitas['id_semester'] ?? null,
                    'nama_semester' => $this->feederAktivitas['nama_semester'] ?? null,
                    'keterangan' => $this->feederAktivitas['keterangan'] ?? null,
                    'sk_tugas' => $this->feederAktivitas['sk_tugas'] ?? null,
                    'tanggal_sk_tugas' => $this->feederAktivitas['tanggal_sk_tugas'] ?? null,
                    'untuk_kampus_merdeka' => ($this->feederAktivitas['untuk_kampus_merdeka'] ?? '0') === '1',
                    'tanggal_mulai' => $this->feederAktivitas['tanggal_mulai'] ?? null,
                    'tanggal_selesai' => $this->feederAktivitas['tanggal_selesai'] ?? null,
                ]
            );

            Log::debug('Aktivitas mahasiswa synced', [
                'id_anggota' => $anggota['id_anggota'],
                'nim' => $anggota['nim'],
                'judul' => $anggota['judul'],
                'was_recently_created' => $aktivitas->wasRecentlyCreated,
            ]);

            // Sync bimbingan TA for this aktivitas (if any)
            $this->syncBimbinganForAktivitas(
                $feederClient,
                $aktivitas,
                $this->feederAktivitas['id_aktivitas']
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync individual aktivitas mahasiswa record', [
                'id_anggota' => $anggota['id_anggota'] ?? 'unknown',
                'nim' => $anggota['nim'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync bimbingan TA (thesis guidance) for the given aktivitas
     *
     * This method is called after successfully creating a new aktivitas mahasiswa.
     * It fetches related bimbingan records from the Feeder API and syncs them to lpr_bimbingan_ta.
     *
     * @param  FeederClient  $feederClient  Feeder client instance
     * @param  LprAktivitasMahasiswa  $aktivitas  The aktivitas record that was just created
     * @param  string  $idAktivitas  The feeder id_aktivitas
     */
    protected function syncBimbinganForAktivitas(
        FeederClient $feederClient,
        LprAktivitasMahasiswa $aktivitas,
        string $idAktivitas
    ): void {
        try {
            // Fetch bimbingan records for this aktivitas
            $filter = ['id_aktivitas' => $idAktivitas];
            $response = $feederClient->getMahasiswaBimbinganDosen($filter, '', 0, 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                Log::debug('No bimbingan data or error for aktivitas', [
                    'id_aktivitas' => $idAktivitas,
                    'error' => $response['error_desc'] ?? 'No data',
                ]);

                return;
            }

            $bimbinganData = $response['data'] ?? [];

            if (empty($bimbinganData)) {
                Log::debug('No bimbingan records for aktivitas', [
                    'id_aktivitas' => $idAktivitas,
                ]);

                return;
            }

            Log::debug('Syncing bimbingan for aktivitas', [
                'id_aktivitas' => $idAktivitas,
                'total_bimbingan' => count($bimbinganData),
            ]);

            // Sync each bimbingan record
            $syncedCount = 0;
            foreach ($bimbinganData as $bimbingan) {
                if ($this->syncBimbinganRecord($bimbingan, $aktivitas)) {
                    $syncedCount++;
                }
            }

            Log::debug('Bimbingan sync completed', [
                'id_aktivitas' => $idAktivitas,
                'synced' => $syncedCount,
                'total' => count($bimbinganData),
            ]);
        } catch (\Exception $e) {
            // Don't fail the aktivitas sync if bimbingan sync fails
            Log::warning('Failed to sync bimbingan for aktivitas', [
                'id_aktivitas' => $idAktivitas,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync individual bimbingan record
     */
    protected function syncBimbinganRecord(array $bimbingan, LprAktivitasMahasiswa $aktivitas): bool
    {
        try {
            // Validate required fields
            if (empty($bimbingan['id_bimbing_mahasiswa'])) {
                Log::warning('Missing id_bimbing_mahasiswa in bimbingan data', [
                    'data' => $bimbingan,
                ]);

                return false;
            }

            // Find dosen by feeder ID
            $dosen = null;
            if (! empty($bimbingan['id_dosen'])) {
                $dosen = Dosen::where('institusi_id', $this->institusiId)
                    ->where('dosen_feeder_id', $bimbingan['id_dosen'])
                    ->first();

                if (! $dosen) {
                    Log::warning('Dosen not found for bimbingan', [
                        'id_dosen' => $bimbingan['id_dosen'],
                        'nama_dosen' => $bimbingan['nama_dosen'] ?? 'unknown',
                    ]);
                    // Don't fail - we'll still save the bimbingan without dosen_id
                }
            }

            // Prepare bimbingan data
            $bimbinganData = [
                'institusi_id' => $this->institusiId,
                'id_aktivitas' => $aktivitas->id,
                'bimbingan_feeder_id' => $bimbingan['id_bimbing_mahasiswa'],
                'aktivitas_feeder_id' => $bimbingan['id_aktivitas'],
                'dosen_id' => $dosen?->id,
                'dosen_feeder_id' => $bimbingan['id_dosen'] ?? null,
                'nama_dosen' => $bimbingan['nama_dosen'] ?? null,
                'pembimbing_ke' => $bimbingan['pembimbing_ke'] ?? null,
                'kategori_kegiatan' => $bimbingan['nama_kategegi_kegiatan'] ?? null,
            ];

            // Check if record exists
            $existingBimbingan = LprBimbinganTa::where('institusi_id', $this->institusiId)
                ->where('bimbingan_feeder_id', $bimbinganData['bimbingan_feeder_id'])
                ->first();

            if ($existingBimbingan) {
                // Update if there are changes
                $existingBimbingan->update($bimbinganData);

                Log::debug('Bimbingan updated', [
                    'id_bimbingan' => $bimbinganData['bimbingan_feeder_id'],
                    'dosen' => $bimbinganData['nama_dosen'],
                ]);
            } else {
                // Create new record
                LprBimbinganTa::create($bimbinganData);

                Log::debug('Bimbingan created', [
                    'id_bimbingan' => $bimbinganData['bimbingan_feeder_id'],
                    'dosen' => $bimbinganData['nama_dosen'],
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync bimbingan record', [
                'id_bimbingan' => $bimbingan['id_bimbing_mahasiswa'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * This is called automatically by Laravel when the job completes
     */
    public function __destruct()
    {
        // Progress tracking is already handled in the handle() method
    }
}
