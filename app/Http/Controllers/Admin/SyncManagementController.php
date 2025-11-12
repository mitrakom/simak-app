<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAkademikMahasiswaJob;
use App\Jobs\SyncAktivitasMahasiswaJob;
use App\Jobs\SyncBimbinganTaJob;
use App\Jobs\SyncDosenAkreditasiJob;
use App\Jobs\SyncDosenJob;
use App\Jobs\SyncLulusanJob;
use App\Jobs\SyncMahasiswaJob;
use App\Jobs\SyncPrestasiMahasiswaJob;
use App\Jobs\SyncProdiJob;
use App\Models\Institusi;
use App\Models\SyncBatchProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class SyncManagementController extends Controller
{
    /**
     * List of available sync jobs with their configurations
     */
    protected function getSyncJobsConfig(): array
    {
        $config = config('sync');

        return [
            'prodi' => [
                'name' => 'Program Studi',
                'description' => 'Sinkronisasi data program studi dari Feeder PDDikti',
                'icon' => 'fa-graduation-cap',
                'color' => 'purple',
                'job_class' => SyncProdiJob::class,
                'has_parameters' => false,
                'parameters' => [],
            ],
            'dosen' => [
                'name' => 'Dosen',
                'description' => 'Sinkronisasi data dosen dari Feeder PDDikti',
                'icon' => 'fa-chalkboard-teacher',
                'color' => 'blue',
                'job_class' => SyncDosenJob::class,
                'has_parameters' => false,
                'parameters' => [],
            ],
            'mahasiswa' => [
                'name' => 'Mahasiswa',
                'description' => 'Sinkronisasi data mahasiswa dari Feeder PDDikti',
                'icon' => 'fa-user-graduate',
                'color' => 'green',
                'job_class' => SyncMahasiswaJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'angkatan' => [
                        'label' => 'Angkatan',
                        'type' => 'text',
                        'default' => $config['mahasiswa']['angkatan'] ?? '2024',
                        'hint' => 'Format: 2024 atau 2020-2024',
                        'required' => false,
                    ],
                    'fetch_biodata_detail' => [
                        'label' => 'Ambil Detail Biodata',
                        'type' => 'checkbox',
                        'default' => $config['mahasiswa']['fetch_biodata_detail'] ?? false,
                        'hint' => 'Mengambil data lengkap biodata mahasiswa',
                        'required' => false,
                    ],
                ],
            ],
            'akademik_mahasiswa' => [
                'name' => 'Akademik Mahasiswa',
                'description' => 'Sinkronisasi data riwayat pendidikan mahasiswa',
                'icon' => 'fa-book-open',
                'color' => 'indigo',
                'job_class' => SyncAkademikMahasiswaJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'semester' => [
                        'label' => 'Semester',
                        'type' => 'text',
                        'default' => $config['akademik_mahasiswa']['semester'] ?? '20241',
                        'hint' => 'Format: 20241 (tahun + semester)',
                        'required' => false,
                    ],
                ],
            ],
            'lulusan' => [
                'name' => 'Lulusan',
                'description' => 'Sinkronisasi data mahasiswa lulusan',
                'icon' => 'fa-user-check',
                'color' => 'emerald',
                'job_class' => SyncLulusanJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'tahun_lulus' => [
                        'label' => 'Tahun Lulus',
                        'type' => 'text',
                        'default' => $config['lulusan']['tahun_lulus'] ?? '2024',
                        'hint' => 'Format: 2024 atau 2020-2024',
                        'required' => false,
                    ],
                ],
            ],
            'dosen_akreditasi' => [
                'name' => 'Dosen Akreditasi',
                'description' => 'Sinkronisasi data dosen untuk akreditasi',
                'icon' => 'fa-certificate',
                'color' => 'cyan',
                'job_class' => SyncDosenAkreditasiJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'tahun_ajaran' => [
                        'label' => 'Tahun Ajaran',
                        'type' => 'text',
                        'default' => $config['dosen_akreditasi']['tahun_ajaran'] ?? '2024',
                        'hint' => 'Format: 2024',
                        'required' => false,
                    ],
                ],
            ],
            'bimbingan_ta' => [
                'name' => 'Bimbingan Tugas Akhir',
                'description' => 'Sinkronisasi data bimbingan tugas akhir mahasiswa',
                'icon' => 'fa-clipboard-check',
                'color' => 'orange',
                'job_class' => SyncBimbinganTaJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'tahun_ajaran' => [
                        'label' => 'Tahun Ajaran',
                        'type' => 'text',
                        'default' => $config['bimbingan_ta']['tahun_ajaran'] ?? '2024',
                        'hint' => 'Format: 2024',
                        'required' => false,
                    ],
                ],
            ],
            'prestasi_mahasiswa' => [
                'name' => 'Prestasi Mahasiswa',
                'description' => 'Sinkronisasi data prestasi mahasiswa',
                'icon' => 'fa-trophy',
                'color' => 'yellow',
                'job_class' => SyncPrestasiMahasiswaJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'tahun' => [
                        'label' => 'Tahun',
                        'type' => 'text',
                        'default' => $config['prestasi_mahasiswa']['tahun'] ?? '2024',
                        'hint' => 'Format: 2024',
                        'required' => false,
                    ],
                ],
            ],
            'aktivitas_mahasiswa' => [
                'name' => 'Aktivitas Mahasiswa',
                'description' => 'Sinkronisasi data aktivitas mahasiswa (MBKM, KKN, dll)',
                'icon' => 'fa-tasks',
                'color' => 'pink',
                'job_class' => SyncAktivitasMahasiswaJob::class,
                'has_parameters' => true,
                'parameters' => [
                    'semester' => [
                        'label' => 'Semester',
                        'type' => 'text',
                        'default' => $config['aktivitas_mahasiswa']['semester'] ?? '20241',
                        'hint' => 'Format: 20241 (tahun + semester)',
                        'required' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Display sync management page
     */
    public function index(Request $request, string $institusi)
    {
        $institusiModel = Institusi::where('slug', $institusi)->firstOrFail();
        $syncJobs = $this->getSyncJobsConfig();

        // Get all active sync processes for this institution
        $activeSyncs = SyncBatchProgress::where('institusi_id', $institusiModel->id)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get recent completed syncs (last 10)
        $recentSyncs = SyncBatchProgress::where('institusi_id', $institusiModel->id)
            ->whereIn('status', ['completed', 'failed'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.sync.management', [
            'institusi' => $institusiModel,
            'syncJobs' => $syncJobs,
            'activeSyncs' => $activeSyncs,
            'recentSyncs' => $recentSyncs,
        ]);
    }

    /**
     * Sync all jobs
     */
    public function syncAll(Request $request, string $institusi)
    {
        $institusiModel = Institusi::where('slug', $institusi)->firstOrFail();
        $syncJobs = $this->getSyncJobsConfig();

        $validated = $request->validate([
            'parameters' => 'nullable|array',
        ]);

        $parameters = $validated['parameters'] ?? [];
        $dispatchedJobs = [];

        foreach ($syncJobs as $jobKey => $jobConfig) {
            try {
                $syncProcessId = uniqid("sync_{$jobKey}_{$institusiModel->id}_", true);
                $jobClass = $jobConfig['job_class'];

                // Build job arguments
                $jobArgs = [$institusiModel->id, $syncProcessId];

                // Add parameters if job has them
                if ($jobConfig['has_parameters'] && isset($parameters[$jobKey])) {
                    foreach ($jobConfig['parameters'] as $paramKey => $paramConfig) {
                        $value = $parameters[$jobKey][$paramKey] ?? $paramConfig['default'];

                        // Convert to proper type
                        if ($paramConfig['type'] === 'checkbox') {
                            $value = (bool) $value;
                        } elseif (empty($value)) {
                            $value = null;
                        }

                        $jobArgs[] = $value;
                    }
                }

                // Dispatch job
                $job = new $jobClass(...$jobArgs);
                dispatch($job);

                $dispatchedJobs[] = $jobConfig['name'];
            } catch (\Exception $e) {
                Log::error("Failed to dispatch {$jobKey} job", [
                    'error' => $e->getMessage(),
                    'institusi' => $institusiModel->slug,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua job sinkronisasi berhasil dijadwalkan',
            'dispatched_jobs' => $dispatchedJobs,
        ]);
    }

    /**
     * Sync single job
     */
    public function syncSingle(Request $request, string $institusi, string $jobKey)
    {
        Log::info('SyncSingle called', [
            'institusi' => $institusi,
            'jobKey' => $jobKey,
            'request_data' => $request->all(),
        ]);

        $institusiModel = Institusi::where('slug', $institusi)->firstOrFail();
        $syncJobs = $this->getSyncJobsConfig();

        if (! isset($syncJobs[$jobKey])) {
            Log::warning('Job key not found', ['jobKey' => $jobKey]);

            return response()->json([
                'success' => false,
                'message' => 'Job tidak ditemukan',
            ], 404);
        }

        $jobConfig = $syncJobs[$jobKey];

        $validated = $request->validate([
            'parameters' => 'nullable|array',
        ]);

        $parameters = $validated['parameters'] ?? [];

        Log::info('Job config loaded', [
            'jobKey' => $jobKey,
            'jobClass' => $jobConfig['job_class'],
            'parameters' => $parameters,
        ]);

        try {
            $syncProcessId = uniqid("sync_{$jobKey}_{$institusiModel->id}_", true);
            $jobClass = $jobConfig['job_class'];

            // Build job arguments
            $jobArgs = [$institusiModel->id, $syncProcessId];

            // Add parameters if job has them
            if ($jobConfig['has_parameters'] && ! empty($parameters)) {
                foreach ($jobConfig['parameters'] as $paramKey => $paramConfig) {
                    $value = $parameters[$paramKey] ?? $paramConfig['default'];

                    // Convert to proper type
                    if ($paramConfig['type'] === 'checkbox') {
                        $value = (bool) $value;
                    } elseif (empty($value)) {
                        $value = null;
                    }

                    $jobArgs[] = $value;
                }
            }

            // Dispatch job
            $job = new $jobClass(...$jobArgs);
            dispatch($job);

            Log::info('Job dispatched successfully', [
                'jobKey' => $jobKey,
                'jobClass' => $jobClass,
                'syncProcessId' => $syncProcessId,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Job {$jobConfig['name']} berhasil dijadwalkan",
                'sync_process_id' => $syncProcessId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch {$jobKey} job", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'institusi' => $institusiModel->slug,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menjadwalkan job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get progress of all sync jobs
     */
    public function getProgress(Request $request, string $institusi)
    {
        $institusiModel = Institusi::where('slug', $institusi)->firstOrFail();

        $syncs = SyncBatchProgress::where('institusi_id', $institusiModel->id)
            ->whereIn('status', ['pending', 'processing'])
            ->get()
            ->map(function ($sync) {
                return [
                    'id' => $sync->id,
                    'batch_id' => $sync->batch_id,
                    'sync_type' => $sync->sync_type,
                    'status' => $sync->status,
                    'total_records' => $sync->total_records,
                    'processed_records' => $sync->processed_records,
                    'success_count' => $sync->success_count,
                    'error_count' => $sync->error_count,
                    'percentage' => $sync->total_records > 0
                        ? round(($sync->processed_records / $sync->total_records) * 100, 2)
                        : 0,
                    'started_at' => $sync->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $sync->completed_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'syncs' => $syncs,
        ]);
    }

    /**
     * Cancel/Delete a sync job
     */
    public function cancelJob(Request $request, string $institusi, int $batchId)
    {
        $institusiModel = Institusi::where('slug', $institusi)->firstOrFail();

        $batchProgress = SyncBatchProgress::where('id', $batchId)
            ->where('institusi_id', $institusiModel->id)
            ->firstOrFail();

        try {
            // Update status to cancelled
            $batchProgress->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            // Try to delete pending jobs from queue (best effort)
            // Note: This requires queue worker to be running and checking for cancelled status

            return response()->json([
                'success' => true,
                'message' => 'Job berhasil dibatalkan',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel job', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan job: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete completed/failed sync record
     */
    public function deleteSync(Request $request, string $institusi, int $batchId)
    {
        $institusiModel = Institusi::where('slug', $institusi)->firstOrFail();

        $batchProgress = SyncBatchProgress::where('id', $batchId)
            ->where('institusi_id', $institusiModel->id)
            ->whereIn('status', ['completed', 'failed', 'cancelled'])
            ->firstOrFail();

        try {
            $batchProgress->delete();

            return response()->json([
                'success' => true,
                'message' => 'Riwayat sinkronisasi berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete sync record', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus riwayat: '.$e->getMessage(),
            ], 500);
        }
    }
}
