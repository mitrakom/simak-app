<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Jobs\SyncAkademikMahasiswaJob;
use App\Jobs\SyncAktivitasMahasiswaJob;
use App\Jobs\SyncDosenAkreditasiJob;
use App\Jobs\SyncDosenJob;
use App\Jobs\SyncLulusanJob;
use App\Jobs\SyncMahasiswaJob;
use App\Jobs\SyncPenelitianDosenJob;
use App\Jobs\SyncPrestasiMahasiswaJob;
use App\Jobs\SyncProdiJob;
use App\Models\Institusi;
use App\Models\SyncBatchProgress;
use App\Models\SyncJobConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Synchronize extends Component
{
    public ?Institusi $institusi = null;

    public array $jobs = [];

    public array $expandedRows = [];

    public array $jobParameters = [];

    public bool $syncingAll = false;

    public string $successMessage = '';

    public string $errorMessage = '';

    public int $refreshCount = 0; // Force re-render on poll

    protected $listeners = ['refreshProgress' => '$refresh'];

    public function mount(): void
    {
        // Get institusi from route or authenticated user
        $institusiSlug = request()->route('institusi')
            ? request()->route('institusi')->slug
            : Auth::user()->institusi->slug;

        $this->institusi = Institusi::where('slug', $institusiSlug)->firstOrFail();

        $this->initializeJobs();
        $this->loadJobConfigurations();
    }

    protected function initializeJobs(): void
    {
        $this->jobs = [
            [
                'id' => 'sync_prodi',
                'name' => 'Sinkronisasi Program Studi',
                'description' => 'Sinkronisasi data program studi dari Feeder',
                'class' => SyncProdiJob::class,
                'category' => 'master',
                'icon' => 'academic-cap',
                'color' => 'blue',
                'parameters' => [],
                'has_parameters' => false,
            ],
            [
                'id' => 'sync_dosen',
                'name' => 'Sinkronisasi Dosen',
                'description' => 'Sinkronisasi data dosen dari Feeder',
                'class' => SyncDosenJob::class,
                'category' => 'dosen',
                'icon' => 'user-group',
                'color' => 'purple',
                'parameters' => [],
                'has_parameters' => false,
            ],
            [
                'id' => 'sync_mahasiswa',
                'name' => 'Sinkronisasi Mahasiswa',
                'description' => 'Sinkronisasi data mahasiswa dari Feeder (Parameter Angkatan WAJIB diisi)',
                'class' => SyncMahasiswaJob::class,
                'category' => 'mahasiswa',
                'icon' => 'users',
                'color' => 'green',
                'parameters' => [
                    'angkatan' => [
                        'type' => 'text',
                        'label' => 'Angkatan (WAJIB) *',
                        'placeholder' => 'Contoh: 2024 atau 2020-2024',
                        'helper' => 'Format: 2024 (tahun tunggal) atau 2020-2024 (range tahun)',
                        'required' => true,
                        'default' => null,
                    ],
                    'fetch_biodata_detail' => [
                        'type' => 'boolean',
                        'label' => 'Ambil Detail Biodata',
                        'helper' => 'Mengambil detail lengkap biodata mahasiswa dari Feeder',
                        'default' => false,
                    ],
                ],
                'has_parameters' => true,
            ],
            [
                'id' => 'sync_akademik_mahasiswa',
                'name' => 'Sinkronisasi Akademik Mahasiswa',
                'description' => 'Sinkronisasi data akademik dan KRS mahasiswa',
                'class' => SyncAkademikMahasiswaJob::class,
                'category' => 'mahasiswa',
                'icon' => 'book-open',
                'color' => 'indigo',
                'parameters' => [
                    'semester_id' => ['type' => 'text', 'label' => 'ID Semester', 'default' => null],
                ],
                'has_parameters' => true,
            ],
            [
                'id' => 'sync_aktivitas_mahasiswa',
                'name' => 'Sinkronisasi Aktivitas Mahasiswa',
                'description' => 'Sinkronisasi aktivitas kuliah mahasiswa (MBKM, dll) - Otomatis sync bimbingan TA',
                'class' => SyncAktivitasMahasiswaJob::class,
                'category' => 'mahasiswa',
                'icon' => 'clipboard-document-list',
                'color' => 'cyan',
                'parameters' => [
                    'semester' => [
                        'type' => 'text',
                        'label' => 'Semester (WAJIB) *',
                        'placeholder' => 'Contoh: 20241',
                        'helper' => 'Format: 20241 (2024 semester 1). Otomatis sync bimbingan TA untuk aktivitas yang di-sync.',
                        'required' => true,
                        'default' => null,
                    ],
                ],
                'has_parameters' => true,
            ],
            [
                'id' => 'sync_prestasi_mahasiswa',
                'name' => 'Sinkronisasi Prestasi Mahasiswa',
                'description' => 'Sinkronisasi data prestasi mahasiswa',
                'class' => SyncPrestasiMahasiswaJob::class,
                'category' => 'mahasiswa',
                'icon' => 'trophy',
                'color' => 'yellow',
                'parameters' => [],
                'has_parameters' => false,
            ],
            [
                'id' => 'sync_lulusan',
                'name' => 'Sinkronisasi Lulusan',
                'description' => 'Sinkronisasi data mahasiswa lulusan',
                'class' => SyncLulusanJob::class,
                'category' => 'mahasiswa',
                'icon' => 'academic-cap',
                'color' => 'emerald',
                'parameters' => [],
                'has_parameters' => false,
            ],
            // REMOVED: sync_bimbingan_ta - now included in sync_aktivitas_mahasiswa
            // [
            //     'id' => 'sync_bimbingan_ta',
            //     'name' => 'Sinkronisasi Bimbingan Tugas Akhir',
            //     'description' => 'Sinkronisasi data bimbingan tugas akhir',
            //     'class' => SyncBimbinganTaJob::class,
            //     'category' => 'akademik',
            //     'icon' => 'document-text',
            //     'color' => 'pink',
            //     'parameters' => [],
            //     'has_parameters' => false,
            // ],
            [
                'id' => 'sync_dosen_akreditasi',
                'name' => 'Sinkronisasi Dosen Akreditasi',
                'description' => 'Sinkronisasi data dosen untuk akreditasi',
                'class' => SyncDosenAkreditasiJob::class,
                'category' => 'dosen',
                'icon' => 'shield-check',
                'color' => 'orange',
                'parameters' => [],
                'has_parameters' => false,
            ],
            [
                'id' => 'sync_penelitian_dosen',
                'name' => 'Sinkronisasi Penelitian Dosen',
                'description' => 'Sinkronisasi data penelitian dosen',
                'class' => SyncPenelitianDosenJob::class,
                'category' => 'dosen',
                'icon' => 'beaker',
                'color' => 'teal',
                'parameters' => [],
                'has_parameters' => false,
            ],
        ];
    }

    protected function loadJobConfigurations(): void
    {
        $configs = SyncJobConfiguration::where('institusi_id', $this->institusi->id)->get();

        foreach ($this->jobs as &$job) {
            $config = $configs->firstWhere('job_class', $job['class']);

            if ($config && $config->default_parameters) {
                foreach ($config->default_parameters as $key => $value) {
                    if (isset($job['parameters'][$key])) {
                        $job['parameters'][$key]['default'] = $value;
                        $this->jobParameters[$job['id']][$key] = $value;
                    }
                }
            }

            // Initialize parameters with defaults
            if ($job['has_parameters']) {
                foreach ($job['parameters'] as $key => $param) {
                    if (! isset($this->jobParameters[$job['id']][$key])) {
                        $this->jobParameters[$job['id']][$key] = $param['default'];
                    }
                }
            }
        }
    }

    public function toggleRow(string $jobId): void
    {
        if (in_array($jobId, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$jobId]);
        } else {
            $this->expandedRows[] = $jobId;
        }
    }

    public function syncJob(string $jobId): void
    {
        // Clear previous messages
        $this->errorMessage = '';
        $this->successMessage = '';

        $job = collect($this->jobs)->firstWhere('id', $jobId);

        if (! $job) {
            $this->errorMessage = 'Job tidak ditemukan';

            return;
        }

        Log::info('Synchronize syncJob called', [
            'job_id' => $jobId,
            'job_name' => $job['name'],
            'has_parameters' => $job['has_parameters'],
            'parameters' => $this->jobParameters[$jobId] ?? [],
        ]);

        try {
            // Validasi parameter untuk sync_mahasiswa
            if ($jobId === 'sync_mahasiswa') {
                $angkatan = $this->jobParameters[$jobId]['angkatan'] ?? null;

                if (empty($angkatan)) {
                    $this->errorMessage = 'Parameter Angkatan wajib diisi untuk sinkronisasi mahasiswa! Format: 2024 atau 2020-2024';

                    return;
                }

                // Validasi format angkatan
                if (! preg_match('/^\d{4}(-\d{4})?$/', $angkatan)) {
                    $this->errorMessage = 'Format angkatan tidak valid! Gunakan format: 2024 (tahun tunggal) atau 2020-2024 (range tahun)';

                    return;
                }

                // Validasi range jika menggunakan format range
                if (str_contains($angkatan, '-')) {
                    [$start, $end] = explode('-', $angkatan);
                    if ((int) $start >= (int) $end) {
                        $this->errorMessage = 'Tahun awal harus lebih kecil dari tahun akhir!';

                        return;
                    }
                }
            }

            // Validasi parameter untuk sync_aktivitas_mahasiswa
            if ($jobId === 'sync_aktivitas_mahasiswa') {
                $semester = $this->jobParameters[$jobId]['semester'] ?? null;

                if (empty($semester)) {
                    $this->errorMessage = 'Parameter Semester wajib diisi untuk sinkronisasi aktivitas mahasiswa! Format: 20241 (2024 semester 1)';

                    return;
                }

                // Validasi format semester (5 digit: YYYYS)
                if (! preg_match('/^\d{5}$/', $semester)) {
                    $this->errorMessage = 'Format semester tidak valid! Gunakan format: 20241 (2024 semester 1) atau 20242 (2024 semester 2)';

                    return;
                }

                // Validasi digit terakhir (semester harus 1, 2, atau 3)
                $semesterDigit = (int) substr($semester, -1);
                if ($semesterDigit < 1 || $semesterDigit > 3) {
                    $this->errorMessage = 'Semester tidak valid! Digit terakhir harus 1 (Ganjil), 2 (Genap), atau 3 (Pendek)';

                    return;
                }
            }

            $syncProcessId = uniqid('sync_' . $jobId . '_', true);

            // All jobs now use institusiId (int) for multitenancy
            $parameters = [$this->institusi->id, $syncProcessId];

            // Add job-specific parameters based on job type
            if ($jobId === 'sync_mahasiswa') {
                $parameters[] = $this->jobParameters[$jobId]['angkatan'] ?? null;
                $parameters[] = $this->jobParameters[$jobId]['fetch_biodata_detail'] ?? false;
            } elseif ($jobId === 'sync_aktivitas_mahasiswa') {
                $parameters[] = $this->jobParameters[$jobId]['semester'] ?? null;
            } elseif ($jobId === 'sync_akademik_mahasiswa') {
                $parameters[] = $this->jobParameters[$jobId]['semester_id'] ?? null;
            } elseif ($job['has_parameters'] && isset($this->jobParameters[$jobId])) {
                // For other jobs with parameters, add them in order
                foreach ($this->jobParameters[$jobId] as $value) {
                    $parameters[] = $value;
                }
            }

            // Dispatch job
            $jobClass = $job['class'];
            $jobClass::dispatch(...$parameters);

            Log::info('Job dispatched successfully', [
                'job_id' => $jobId,
                'job_class' => $jobClass,
                'parameters' => $parameters,
            ]);

            // Save parameters configuration
            $this->saveJobConfiguration($job);

            $this->successMessage = "Job '{$job['name']}' berhasil dijalankan!";
            $this->dispatch('refreshProgress');
        } catch (\Exception $e) {
            $this->errorMessage = "Gagal menjalankan job: {$e->getMessage()}";
            Log::error('Synchronize syncJob error', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function cancelJob(string $jobId): void
    {
        try {
            // Use LIKE for sync types that may have suffix (angkatan, semester, tahun)
            $useLike = in_array($jobId, ['mahasiswa', 'lulusan', 'aktivitas_mahasiswa', 'prestasi_mahasiswa']);

            $query = SyncBatchProgress::where('institusi_id', $this->institusi->id)
                ->where('status', 'processing')
                ->latest();

            if ($useLike) {
                $query->where('sync_type', 'like', $jobId . '%');
            } else {
                $query->where('sync_type', $jobId);
            }

            $latestBatch = $query->first();

            if (! $latestBatch) {
                $this->errorMessage = 'Tidak ada job yang sedang berjalan untuk dibatalkan';

                return;
            }

            // Mark batch as cancelled
            $latestBatch->markCancelled('Dibatalkan oleh pengguna');

            // Cancel the Laravel batch if it exists
            if ($latestBatch->batch_id) {
                $batch = \Illuminate\Support\Facades\Bus::findBatch($latestBatch->batch_id);
                if ($batch && ! $batch->finished()) {
                    $batch->cancel();
                }
            }

            $this->successMessage = 'Job berhasil dibatalkan';
            $this->dispatch('refreshProgress');
        } catch (\Exception $e) {
            $this->errorMessage = "Gagal membatalkan job: {$e->getMessage()}";
        }
    }

    public function syncAll(): void
    {
        $this->syncingAll = true;
        $jobsDispatched = 0;

        try {
            foreach ($this->jobs as $job) {
                $syncProcessId = uniqid('sync_all_' . $job['id'] . '_', true);

                // Check if job expects Institusi object or int
                $jobClass = $job['class'];
                $reflection = new \ReflectionClass($jobClass);
                $constructor = $reflection->getConstructor();
                $firstParam = $constructor->getParameters()[0] ?? null;

                // Determine if first parameter expects Institusi object or int
                $expectsObject = $firstParam && $firstParam->getType()?->getName() === Institusi::class;
                $parameters = [$expectsObject ? $this->institusi : $this->institusi->id, $syncProcessId];

                // Add job-specific parameters
                if ($job['has_parameters'] && isset($this->jobParameters[$job['id']])) {
                    foreach ($this->jobParameters[$job['id']] as $value) {
                        $parameters[] = $value;
                    }
                }

                $jobClass::dispatch(...$parameters);

                $this->saveJobConfiguration($job);
                $jobsDispatched++;
            }

            $this->successMessage = "Berhasil menjalankan {$jobsDispatched} jobs!";
            $this->dispatch('refreshProgress');
        } catch (\Exception $e) {
            $this->errorMessage = "Gagal menjalankan sync all: {$e->getMessage()}";
        } finally {
            $this->syncingAll = false;
        }
    }

    protected function saveJobConfiguration(array $job): void
    {
        if (! $job['has_parameters']) {
            return;
        }

        $parameters = $this->jobParameters[$job['id']] ?? [];

        SyncJobConfiguration::updateOrCreate(
            [
                'institusi_id' => $this->institusi->id,
                'job_class' => $job['class'],
            ],
            [
                'job_name' => $job['name'],
                'description' => $job['description'],
                'default_parameters' => $parameters,
                'category' => $job['category'],
            ]
        );
    }

    public function getJobProgress(string $jobId): ?array
    {
        $job = collect($this->jobs)->firstWhere('id', $jobId);
        if (! $job) {
            return null;
        }

        // Map job ID to sync_type
        $syncTypeMap = [
            'sync_prodi' => 'prodi',
            'sync_dosen' => 'dosen',
            'sync_mahasiswa' => 'mahasiswa',
            'sync_akademik_mahasiswa' => 'akademik_mahasiswa',
            'sync_aktivitas_mahasiswa' => 'aktivitas_mahasiswa',
            'sync_prestasi_mahasiswa' => 'prestasi_mahasiswa',
            'sync_lulusan' => 'lulusan',
            'sync_bimbingan_ta' => 'bimbingan_ta',
            'sync_dosen_akreditasi' => 'dosen_akreditasi',
            'sync_penelitian_dosen' => 'penelitian_dosen',
        ];

        $syncType = $syncTypeMap[$jobId] ?? null;
        if (! $syncType) {
            return null;
        }

        // Use LIKE for sync types that may have suffix (e.g., mahasiswa_2024, lulusan_angkatan_2020)
        $useLike = in_array($syncType, ['mahasiswa', 'lulusan', 'aktivitas_mahasiswa', 'prestasi_mahasiswa']);

        $query = SyncBatchProgress::where('institusi_id', $this->institusi->id);

        if ($useLike) {
            $query->where('sync_type', 'like', $syncType . '%');
        } else {
            $query->where('sync_type', $syncType);
        }

        $latestBatch = $query->latest('id')->first();

        if (! $latestBatch) {
            return null;
        }

        // APPROACH #1: Get real-time progress from Laravel Batch (100% accurate!)
        $laravelBatch = null;
        if ($latestBatch->batch_id && $latestBatch->status === 'processing') {
            try {
                $laravelBatch = Bus::findBatch($latestBatch->batch_id);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch Laravel batch', [
                    'batch_id' => $latestBatch->batch_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If batch is processing, get real-time stats from Laravel Batch
        if ($laravelBatch && ! $laravelBatch->finished()) {
            $processedJobs = $laravelBatch->processedJobs();
            $totalJobs = $laravelBatch->totalJobs;
            $progressPercentage = $totalJobs > 0 ? round(($processedJobs / $totalJobs) * 100, 1) : 0;

            return [
                'status' => 'processing',
                'progress' => $progressPercentage,
                'total' => $totalJobs,
                'processed' => $processedJobs,
                'failed' => $laravelBatch->failedJobs,
                'started_at' => $latestBatch->started_at,
                'completed_at' => null,
            ];
        }

        // If Laravel Batch is finished but DB not yet updated, show completed status
        if ($laravelBatch && $laravelBatch->finished() && $latestBatch->status === 'processing') {
            $totalJobs = $laravelBatch->totalJobs;
            $failedJobs = $laravelBatch->failedJobs;

            return [
                'status' => $failedJobs > 0 ? 'failed' : 'completed',
                'progress' => 100,
                'total' => $totalJobs,
                'processed' => $totalJobs,
                'failed' => $failedJobs,
                'started_at' => $latestBatch->started_at,
                'completed_at' => now(), // Use current time until DB updates
            ];
        }

        // If finished or no Laravel batch, use stored data
        return [
            'status' => $latestBatch->status,
            'progress' => $latestBatch->progress_percentage,
            'processed' => $latestBatch->processed_records,
            'total' => $latestBatch->total_records,
            'failed' => $latestBatch->failed_records,
            'started_at' => $latestBatch->started_at,
            'completed_at' => $latestBatch->completed_at,
        ];
    }

    public function refreshAllProgress(): void
    {
        // Increment refresh count to force Livewire to detect change and re-render
        $this->refreshCount++;

        // This method is called by wire:poll to refresh all job progress
        // The UI will automatically re-render with updated progress
        $this->dispatch('progressUpdated');
    }

    public function getOverallStats(): array
    {
        $stats = [
            'total_jobs' => count($this->jobs),
            'running' => 0,
            'completed' => 0,
            'failed' => 0,
            'pending' => count($this->jobs),
        ];

        foreach ($this->jobs as $job) {
            $progress = $this->getJobProgress($job['id']);
            if ($progress) {
                $stats['pending']--;
                if ($progress['status'] === 'processing') {
                    $stats['running']++;
                } elseif ($progress['status'] === 'completed') {
                    $stats['completed']++;
                } elseif ($progress['status'] === 'failed') {
                    $stats['failed']++;
                }
            }
        }

        return $stats;
    }

    public function render()
    {
        return view('livewire.admin.synchronize')->layout('components.layouts.admin');
    }
}
