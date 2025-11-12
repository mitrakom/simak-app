<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Dosen;
use App\Models\Institusi;
use App\Models\LprPenelitianDosen;
use App\Services\FeederClient;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPenelitianDosenJob implements ShouldQueue
{
    use Queueable;

    /**
     * The institusi instance to sync penelitian dosen for
     */
    protected Institusi $institusi;

    /**
     * Batch size untuk processing data penelitian dosen (untuk handling 1000+ records)
     */
    protected int $batchSize = 100;

    /**
     * Create a new job instance.
     */
    public function __construct(Institusi $institusi)
    {
        $this->institusi = $institusi;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting SyncPenelitianDosenJob', [
            'institusi_id' => $this->institusi->id,
            'institusi_slug' => $this->institusi->slug,
        ]);

        try {
            // Create FeederClient instance untuk institusi ini
            $feederClient = new FeederClient;

            // Set institusi langsung untuk Job context (tanpa user)
            $feederClient->setInstitusi($this->institusi);

            // Fetch data penelitian dosen dari Feeder API (ambil semua dengan limit 0)
            $response = $feederClient->getRiwayatPenelitianDosen([], '', 0, 0);

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                throw new Exception('Failed to fetch penelitian dosen data from Feeder API: '.($response['error_desc'] ?? 'Unknown error'));
            }

            $penelitianDosenData = $response['data'] ?? [];
            $syncedCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            $skippedCount = 0;

            Log::info('Fetched penelitian dosen data from Feeder', [
                'institusi_slug' => $this->institusi->slug,
                'total_penelitian_dosen' => count($penelitianDosenData),
            ]);

            // Process data penelitian dosen dalam batches untuk performa yang lebih baik
            $batches = array_chunk($penelitianDosenData, $this->batchSize);

            foreach ($batches as $batchIndex => $batch) {
                Log::debug('Processing penelitian dosen batch', [
                    'institusi_slug' => $this->institusi->slug,
                    'batch_index' => $batchIndex + 1,
                    'batch_size' => count($batch),
                    'total_batches' => count($batches),
                ]);

                DB::transaction(function () use ($batch, &$syncedCount, &$updatedCount, &$createdCount, &$skippedCount) {
                    foreach ($batch as $feederPenelitianDosen) {
                        $this->syncPenelitianDosen($feederPenelitianDosen, $syncedCount, $updatedCount, $createdCount, $skippedCount);
                    }
                });
            }

            Log::info('SyncPenelitianDosenJob completed successfully', [
                'institusi_slug' => $this->institusi->slug,
                'total_synced' => $syncedCount,
                'created' => $createdCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
                'total_batches_processed' => count($batches),
            ]);
        } catch (Exception $e) {
            Log::error('SyncPenelitianDosenJob failed', [
                'institusi_slug' => $this->institusi->slug,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e; // Re-throw untuk memicu job retry jika dikonfigurasi
        }
    }

    /**
     * Sync individual penelitian dosen data
     */
    protected function syncPenelitianDosen(array $feederPenelitianDosen, int &$syncedCount, int &$updatedCount, int &$createdCount, int &$skippedCount): void
    {
        try {
            // Validate required fields
            if (empty($feederPenelitianDosen['id_dosen']) || empty($feederPenelitianDosen['id_penelitian'])) {
                Log::warning('Missing required fields in penelitian dosen data', [
                    'institusi_slug' => $this->institusi->slug,
                    'data' => $feederPenelitianDosen,
                ]);
                $skippedCount++;

                return;
            }

            // Find dosen by feeder ID
            $dosen = Dosen::where('institusi_id', $this->institusi->id)
                ->where('feeder_id', $feederPenelitianDosen['id_dosen'])
                ->first();

            if (! $dosen) {
                Log::warning('Dosen not found for penelitian record', [
                    'institusi_slug' => $this->institusi->slug,
                    'id_dosen_feeder' => $feederPenelitianDosen['id_dosen'],
                    'nama_dosen' => $feederPenelitianDosen['nama_dosen'] ?? 'N/A',
                    'penelitian_data' => $feederPenelitianDosen,
                ]);
                $skippedCount++;

                return;
            }

            // Mapping dari response API Feeder ke field tabel internal
            $penelitianDosenData = [
                'institusi_id' => $this->institusi->id,
                'dosen_id' => $dosen->id,
                'dosen_feeder_id' => $feederPenelitianDosen['id_dosen'], // mapping: dosen_feeder_id => id_dosen
                'penelitian_feeder_id' => $feederPenelitianDosen['id_penelitian'], // mapping: penelitian_feeder_id => id_penelitian
                'nidn' => $feederPenelitianDosen['nidn'] ?? '',
                'nama_dosen' => $feederPenelitianDosen['nama_dosen'] ?? '',
                'judul_penelitian' => $feederPenelitianDosen['judul_penelitian'] ?? '',
                'tahun_kegiatan' => $feederPenelitianDosen['tahun_kegiatan'] ?? '',
                'nama_lembaga_iptek' => $feederPenelitianDosen['nama_lembaga_iptek'] ?? null,
            ];

            // Cari penelitian dosen berdasarkan unique constraint
            $existingPenelitianDosen = LprPenelitianDosen::where('institusi_id', $this->institusi->id)
                ->where('penelitian_feeder_id', $feederPenelitianDosen['id_penelitian'])
                ->where('dosen_feeder_id', $feederPenelitianDosen['id_dosen'])
                ->first();

            if ($existingPenelitianDosen) {
                // Update existing penelitian dosen
                $wasUpdated = false;

                // Cek apakah ada perubahan data
                if (
                    $existingPenelitianDosen->nidn !== $penelitianDosenData['nidn'] ||
                    $existingPenelitianDosen->nama_dosen !== $penelitianDosenData['nama_dosen'] ||
                    $existingPenelitianDosen->judul_penelitian !== $penelitianDosenData['judul_penelitian'] ||
                    $existingPenelitianDosen->tahun_kegiatan !== $penelitianDosenData['tahun_kegiatan'] ||
                    $existingPenelitianDosen->nama_lembaga_iptek !== $penelitianDosenData['nama_lembaga_iptek']
                ) {
                    $existingPenelitianDosen->update($penelitianDosenData);
                    $wasUpdated = true;
                    $updatedCount++;

                    Log::debug('Penelitian dosen updated', [
                        'institusi_slug' => $this->institusi->slug,
                        'penelitian_feeder_id' => $feederPenelitianDosen['id_penelitian'],
                        'dosen_feeder_id' => $feederPenelitianDosen['id_dosen'],
                        'nama_dosen' => $penelitianDosenData['nama_dosen'],
                        'judul_penelitian' => $penelitianDosenData['judul_penelitian'],
                    ]);
                }

                if (! $wasUpdated) {
                    Log::debug('Penelitian dosen unchanged', [
                        'institusi_slug' => $this->institusi->slug,
                        'penelitian_feeder_id' => $feederPenelitianDosen['id_penelitian'],
                        'dosen_feeder_id' => $feederPenelitianDosen['id_dosen'],
                        'nama_dosen' => $penelitianDosenData['nama_dosen'],
                        'judul_penelitian' => $penelitianDosenData['judul_penelitian'],
                    ]);
                }
            } else {
                // Create new penelitian dosen
                LprPenelitianDosen::create($penelitianDosenData);
                $createdCount++;

                Log::debug('Penelitian dosen created', [
                    'institusi_slug' => $this->institusi->slug,
                    'penelitian_feeder_id' => $feederPenelitianDosen['id_penelitian'],
                    'dosen_feeder_id' => $feederPenelitianDosen['id_dosen'],
                    'nama_dosen' => $penelitianDosenData['nama_dosen'],
                    'judul_penelitian' => $penelitianDosenData['judul_penelitian'],
                ]);
            }

            $syncedCount++;
        } catch (Exception $e) {
            Log::error('Failed to sync individual penelitian dosen', [
                'institusi_slug' => $this->institusi->slug,
                'penelitian_feeder_id' => $feederPenelitianDosen['id_penelitian'] ?? 'unknown',
                'dosen_feeder_id' => $feederPenelitianDosen['id_dosen'] ?? 'unknown',
                'dosen_nama' => $feederPenelitianDosen['nama_dosen'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Jangan throw exception untuk individual penelitian dosen failure
            // Lanjutkan dengan penelitian dosen lainnya
            $skippedCount++;
        }
    }
}
