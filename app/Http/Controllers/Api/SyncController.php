<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

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
use App\Models\SyncStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    /**
     * Get semua status sinkronisasi untuk institusi user yang sedang login
     * GET /api/sync/status-all
     */
    public function getAllStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'user_has_no_institusi',
                ], 422);
            }

            // Get semua status sync untuk institusi ini
            $syncStatuses = SyncStatus::getLastStatusForInstitusi($institusi->id);

            // Calculate overall status
            $overallStatus = 'tersinkronisasi';
            $errorCount = collect($syncStatuses)->where('status', 'error')->count();
            $pendingCount = collect($syncStatuses)->where('status', 'pending')->count();
            $syncingCount = collect($syncStatuses)->whereIn('status', ['sinkronisasi', 'memulai'])->count();

            if ($syncingCount > 0) {
                $overallStatus = 'sinkronisasi';
            } elseif ($errorCount > 0) {
                $overallStatus = 'error';
            } elseif ($pendingCount > 0) {
                $overallStatus = 'pending';
            }

            // Get last overall sync time
            $lastOverallSync = SyncStatus::where('institusi_id', $institusi->id)
                ->whereNotNull('last_sync_time')
                ->orderByDesc('last_sync_time')
                ->first()?->last_sync_time;

            return response()->json([
                'status' => 'success',
                'data' => $syncStatuses,
                'metadata' => [
                    'overall_status' => $overallStatus,
                    'last_overall_sync' => $lastOverallSync?->toIso8601String(),
                    'error_count' => $errorCount,
                    'pending_count' => $pendingCount,
                    'syncing_count' => $syncingCount,
                    'success_count' => collect($syncStatuses)->where('status', 'tersinkronisasi')->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil status sinkronisasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get status sinkronisasi terakhir untuk sync type spesifik
     * GET /api/sync/{syncType}/last-status
     */
    public function getLastStatus(Request $request, string $syncType): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'user_has_no_institusi',
                ], 422);
            }

            // Validate sync type
            $validSyncTypes = [
                'dosen',
                'mahasiswa',
                'prodi',
                'nilai_mahasiswa',
                'akademik_mahasiswa',
                'prestasi_mahasiswa',
                'bimbingan_ta',
                'lulusan',
                'aktivitas_mahasiswa',
                'dosen_akreditasi',
            ];

            if (! in_array($syncType, $validSyncTypes)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sync type tidak valid',
                    'error' => 'invalid_sync_type',
                    'valid_types' => $validSyncTypes,
                ], 422);
            }

            // Get last status untuk sync type ini
            $status = SyncStatus::getLastStatusBySyncType($institusi->id, $syncType);

            if (! $status) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'sync_type' => $syncType,
                        'label' => $this->getSyncTypeLabel($syncType),
                        'icon' => $this->getSyncTypeIcon($syncType),
                        'status' => 'pending',
                        'last_sync_time' => null,
                        'total_records' => 0,
                        'current_progress' => 0,
                        'progress_message' => null,
                        'error_message' => null,
                        'sync_process_id' => null,
                    ],
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'data' => $status,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil status sinkronisasi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: Get sync type label
     */
    private function getSyncTypeLabel(string $syncType): string
    {
        $labels = [
            'dosen' => 'Data Dosen',
            'mahasiswa' => 'Data Mahasiswa',
            'prodi' => 'Program Studi',
            'nilai_mahasiswa' => 'Nilai Mahasiswa',
            'akademik_mahasiswa' => 'Akademik Mahasiswa',
            'prestasi_mahasiswa' => 'Prestasi Mahasiswa',
            'bimbingan_ta' => 'Bimbingan TA',
            'lulusan' => 'Data Lulusan',
            'aktivitas_mahasiswa' => 'Aktivitas Mahasiswa',
            'dosen_akreditasi' => 'Dosen Akreditasi',
        ];

        return $labels[$syncType] ?? $syncType;
    }

    /**
     * Helper: Get sync type icon
     */
    private function getSyncTypeIcon(string $syncType): string
    {
        $icons = [
            'dosen' => '👨‍🏫',
            'mahasiswa' => '👨‍🎓',
            'prodi' => '🎓',
            'nilai_mahasiswa' => '📊',
            'akademik_mahasiswa' => '📖',
            'prestasi_mahasiswa' => '🏆',
            'bimbingan_ta' => '📚',
            'lulusan' => '👔',
            'aktivitas_mahasiswa' => '📋',
            'dosen_akreditasi' => '🔍',
        ];

        return $icons[$syncType] ?? '📌';
    }

    /**
     * Sync prodi data untuk institusi user yang sedang login
     */
    public function syncProdi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Double check: user must have institusi (should be caught by middleware)
            if (! $user->institusi_id || ! $user->institusi) {
                return response()->json([
                    'message' => 'Forbidden',
                    'error' => 'User tidak memiliki institusi yang valid',
                ], 403);
            }

            $institusi = $user->institusi;

            // Dispatch job dengan institusi_id user yang sedang login
            // Ini memastikan user hanya bisa sync data institusi mereka sendiri
            SyncProdiJob::dispatch($institusi->id);

            return response()->json([
                'message' => 'Sync prodi job has been queued successfully',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync prodi job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync dosen data untuk institusi user yang sedang login
     */
    public function syncDosen(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Check feeder configuration
            if (
                empty($institusi->feeder_url) ||
                empty($institusi->feeder_username) ||
                empty($institusi->feeder_password)
            ) {
                return response()->json([
                    'message' => 'Konfigurasi Feeder tidak lengkap',
                    'error' => 'Institusi harus memiliki konfigurasi feeder yang lengkap (URL, username, password)',
                ], 422);
            }

            // Generate unique sync process ID
            $syncProcessId = uniqid('sync_dosen_'.$institusi->id.'_', true);

            // Dispatch job untuk sync dosen dengan sync process ID (pass institusi ID, not model)
            $job = new SyncDosenJob($institusi->id, $syncProcessId);
            dispatch($job);

            return response()->json([
                'message' => 'Sync dosen job telah diqueue dengan sukses',
                'sync_process_id' => $syncProcessId,
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'info' => 'Job akan memproses data dosen dari API Feeder dan menyimpannya ke database. Gunakan sync_process_id untuk monitoring progress via WebSocket.',
                'websocket_channels' => [
                    'private-sync-process.'.$syncProcessId,
                    'private-institusi-sync.'.$institusi->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menqueue sync dosen job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync mahasiswa data untuk institusi user yang sedang login
     *
     * Request parameters:
     * - angkatan (optional): Filter mahasiswa by angkatan (e.g., "2024", "2023")
     * - fetch_biodata (optional): Boolean to enable fetching biodata details (default: false)
     */
    public function syncMahasiswa(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Check feeder configuration
            if (
                empty($institusi->feeder_url) ||
                empty($institusi->feeder_username) ||
                empty($institusi->feeder_password)
            ) {
                return response()->json([
                    'message' => 'Konfigurasi Feeder tidak lengkap',
                    'error' => 'Institusi harus memiliki konfigurasi feeder yang lengkap (URL, username, password)',
                ], 422);
            }

            // Validate request parameters
            $validated = $request->validate([
                'angkatan' => 'nullable|string|regex:/^\d{4}(-\d{4})?$/', // Supports '2024' or '2020-2024'
                'fetch_biodata' => 'nullable|boolean',
            ]);

            $angkatan = $validated['angkatan'] ?? null;
            $fetchBiodata = $validated['fetch_biodata'] ?? false;

            // Generate unique sync process ID
            $syncProcessId = uniqid('sync_mahasiswa_'.$institusi->id.'_', true);

            // Dispatch job untuk sync mahasiswa (pass institusi ID, not model)
            $job = new SyncMahasiswaJob($institusi->id, $syncProcessId, $angkatan, $fetchBiodata);
            dispatch($job);

            $responseMessage = 'Sync mahasiswa job telah diqueue dengan sukses';
            $responseInfo = 'Job akan memproses data mahasiswa dari API Feeder';

            if ($angkatan) {
                if (str_contains($angkatan, '-')) {
                    $responseInfo .= " (filter angkatan range: {$angkatan})";
                } else {
                    $responseInfo .= " (filter angkatan: {$angkatan})";
                }
            }

            if ($fetchBiodata) {
                $responseInfo .= ' dan mengambil detail biodata mahasiswa';
            }

            return response()->json([
                'message' => $responseMessage,
                'sync_process_id' => $syncProcessId,
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'parameters' => [
                    'angkatan' => $angkatan,
                    'fetch_biodata' => $fetchBiodata,
                ],
                'info' => $responseInfo,
                'websocket_channels' => [
                    'private-sync-process.'.$syncProcessId,
                    'private-institusi-sync.'.$institusi->id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi parameter gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menqueue sync mahasiswa job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync akademik mahasiswa (IPS, IPK, SKS) untuk institusi user yang sedang login
     *
     * Request parameters:
     * - angkatan (optional): Filter by angkatan (e.g., "2024", "2023")
     */
    public function syncAkademikMahasiswa(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Check feeder configuration
            if (
                empty($institusi->feeder_url) ||
                empty($institusi->feeder_username) ||
                empty($institusi->feeder_password)
            ) {
                return response()->json([
                    'message' => 'Konfigurasi Feeder tidak lengkap',
                    'error' => 'Institusi harus memiliki konfigurasi feeder yang lengkap (URL, username, password)',
                ], 422);
            }

            // Validate request parameters
            $validated = $request->validate([
                'semester' => 'nullable|string|regex:/^\d{5}$/', // Format: 20241 (tahun+semester)
            ]);

            $semester = $validated['semester'] ?? null;

            // Generate unique sync process ID
            $syncProcessId = uniqid('sync_akademik_mahasiswa_'.$institusi->id.'_', true);

            // Dispatch job untuk sync akademik mahasiswa (pass institusiId, not model)
            SyncAkademikMahasiswaJob::dispatch($institusi->id, $syncProcessId, $semester);

            $responseMessage = 'Sync akademik mahasiswa job telah diqueue dengan sukses';
            $responseInfo = 'Job akan memproses riwayat akademik mahasiswa (IPS, IPK, SKS) per semester dari API Feeder';

            if ($semester) {
                $responseInfo .= " (filter semester: {$semester})";
            }

            return response()->json([
                'message' => $responseMessage,
                'sync_process_id' => $syncProcessId,
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'parameters' => [
                    'semester' => $semester,
                ],
                'info' => $responseInfo,
                'note' => 'Data mahasiswa harus sudah disinkronkan terlebih dahulu sebelum sync akademik',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi parameter gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menqueue sync akademik mahasiswa job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync bimbingan TA data untuk institusi user yang sedang login
     */
    public function syncBimbinganTa(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Check feeder configuration
            if (
                empty($institusi->feeder_url) ||
                empty($institusi->feeder_username) ||
                empty($institusi->feeder_password)
            ) {
                return response()->json([
                    'message' => 'Konfigurasi Feeder tidak lengkap',
                    'error' => 'Institusi harus memiliki konfigurasi feeder yang lengkap (URL, username, password)',
                ], 422);
            }

            // Dispatch job untuk sync bimbingan TA
            SyncBimbinganTaJob::dispatch($institusi);

            return response()->json([
                'message' => 'Sync bimbingan TA job telah diqueue dengan sukses',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'info' => 'Job akan memproses data bimbingan TA (tugas akhir) dari API Feeder',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menqueue sync bimbingan TA job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync prodi data untuk semua institusi (admin only)
     * SECURITY: Hanya super admin yang bisa sync semua institusi
     */
    public function syncAllProdi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // SECURITY CHECK: Hanya untuk super admin atau user tanpa institusi_id
            // Anda bisa ganti dengan permission check jika menggunakan Spatie Permission
            if ($user->institusi_id) {
                return response()->json([
                    'message' => 'Forbidden',
                    'error' => 'Only super admin can sync all institusi',
                ], 403);
            }

            $institusiList = Institusi::whereNotNull('feeder_url')
                ->whereNotNull('feeder_username')
                ->whereNotNull('feeder_password')
                ->get();

            $queuedCount = 0;
            foreach ($institusiList as $institusi) {
                SyncProdiJob::dispatch($institusi->id);
                $queuedCount++;
            }

            return response()->json([
                'message' => 'Sync prodi jobs have been queued for all institusi',
                'total_institusi' => $queuedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync prodi jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync prodi untuk institusi tertentu (by slug)
     * SECURITY: User hanya bisa sync institusi mereka sendiri
     */
    public function syncProdiBySlug(Request $request, string $slug): JsonResponse
    {
        try {
            $user = Auth::user();

            // SECURITY CHECK: Pastikan slug yang diminta adalah institusi user yang login
            if ($user->institusi->slug !== $slug) {
                return response()->json([
                    'message' => 'Forbidden',
                    'error' => 'You can only sync data for your own institusi',
                ], 403);
            }

            $institusi = $user->institusi;

            // Check feeder configuration
            if (
                empty($institusi->feeder_url) ||
                empty($institusi->feeder_username) ||
                empty($institusi->feeder_password)
            ) {
                return response()->json([
                    'message' => 'Feeder configuration incomplete',
                    'error' => 'Institusi must have complete feeder configuration',
                ], 422);
            }

            // Dispatch job untuk sync prodi
            SyncProdiJob::dispatch($institusi->id);

            return response()->json([
                'message' => 'Sync prodi job has been queued successfully',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync prodi job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync dosen akreditasi data untuk institusi user yang sedang login
     */
    public function syncDosenAkreditasi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Check feeder configuration
            if (
                empty($institusi->feeder_url) ||
                empty($institusi->feeder_username) ||
                empty($institusi->feeder_password)
            ) {
                return response()->json([
                    'message' => 'Feeder configuration incomplete',
                    'error' => 'Institusi must have complete feeder configuration',
                ], 422);
            }

            // Dispatch job untuk sync dosen akreditasi
            SyncDosenAkreditasiJob::dispatch($user);

            return response()->json([
                'message' => 'Sync dosen akreditasi job telah diqueue dengan sukses',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'info' => 'Job akan memproses data riwayat pendidikan, jabatan fungsional, dan sertifikasi dosen untuk laporan akreditasi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync dosen akreditasi job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync data lulusan dari Feeder PDDikti
     */
    public function syncLulusan(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak terkait dengan institusi',
                    'error' => 'User must be associated with an institusi',
                ], 422);
            }

            // Validasi konfigurasi feeder
            if (! $institusi->feeder_url || ! $institusi->feeder_username || ! $institusi->feeder_password) {
                return response()->json([
                    'message' => 'Feeder configuration incomplete',
                    'error' => 'Institusi must have complete feeder configuration',
                ], 422);
            }

            // Validasi dan ambil parameter opsional
            $validatedData = $request->validate([
                'batch_size' => 'sometimes|integer|min:1|max:1000',
                'max_records' => 'sometimes|integer|min:1',
                'start_offset' => 'sometimes|integer|min:0',
                'angkatan_start' => 'sometimes|integer|min:1900|max:2100',
                'angkatan_end' => 'sometimes|integer|min:1900|max:2100|gte:angkatan_start',
                'tahun_keluar' => 'sometimes|integer|min:1900|max:2100',
            ]);

            $batchSize = $validatedData['batch_size'] ?? 100;
            $maxRecords = $validatedData['max_records'] ?? null;
            $startOffset = $validatedData['start_offset'] ?? 0;
            $angkatanStart = isset($validatedData['angkatan_start']) ? (string) $validatedData['angkatan_start'] : null;
            $angkatanEnd = isset($validatedData['angkatan_end']) ? (string) $validatedData['angkatan_end'] : null;
            $tahunKeluar = isset($validatedData['tahun_keluar']) ? (string) $validatedData['tahun_keluar'] : null;

            // Dispatch job untuk sync lulusan dengan parameter
            SyncLulusanJob::dispatch($user, $batchSize, $maxRecords, $startOffset, $angkatanStart, $angkatanEnd, $tahunKeluar);

            return response()->json([
                'message' => 'Sync lulusan job telah diqueue dengan sukses',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'parameters' => [
                    'batch_size' => $batchSize,
                    'max_records' => $maxRecords,
                    'start_offset' => $startOffset,
                    'angkatan_start' => $angkatanStart,
                    'angkatan_end' => $angkatanEnd,
                    'tahun_keluar' => $tahunKeluar,
                ],
                'info' => 'Job akan memproses data mahasiswa lulus/DO untuk laporan dan analisis lulusan',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync lulusan job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync prestasi mahasiswa data untuk institusi user yang sedang login
     */
    public function syncPrestasi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Validasi parameter
            $validated = $request->validate([
                'tahun_prestasi' => 'nullable|integer|min:2000|max:'.(date('Y') + 5),
                'batch_size' => 'nullable|integer|min:1|max:1000',
                'max_records' => 'nullable|integer|min:0',
                'start_offset' => 'nullable|integer|min:0',
            ]);

            $tahunPrestasi = $validated['tahun_prestasi'] ?? null;
            $batchSize = $validated['batch_size'] ?? 100;
            $maxRecords = $validated['max_records'] ?? 0;
            $startOffset = $validated['start_offset'] ?? 0;

            // Dispatch job untuk sync prestasi mahasiswa
            SyncPrestasiMahasiswaJob::dispatch(
                institusiId: $institusi->id,
                tahunPrestasi: $tahunPrestasi,
                batchSize: $batchSize,
                maxRecords: $maxRecords,
                startOffset: $startOffset
            );

            return response()->json([
                'message' => 'Sync prestasi mahasiswa job telah diqueue dengan sukses',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'parameters' => [
                    'tahun_prestasi' => $tahunPrestasi,
                    'batch_size' => $batchSize,
                    'max_records' => $maxRecords,
                    'start_offset' => $startOffset,
                ],
                'info' => 'Job akan memproses data prestasi mahasiswa dari Feeder',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync prestasi mahasiswa job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync aktivitas mahasiswa data untuk institusi user yang sedang login
     */
    public function syncAktivitasMahasiswa(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $institusi = $user->institusi;

            if (! $institusi) {
                return response()->json([
                    'message' => 'User tidak memiliki institusi',
                    'error' => 'User must have an associated institusi',
                ], 422);
            }

            // Validasi parameter
            $validated = $request->validate([
                'semester_start' => 'nullable|string|max:10',
                'semester_end' => 'nullable|string|max:10',
            ]);

            $semesterStart = $validated['semester_start'] ?? null;
            $semesterEnd = $validated['semester_end'] ?? null;

            // Dispatch job untuk sync aktivitas mahasiswa
            // Note: Job hanya support 1 semester filter, gunakan semester_start
            SyncAktivitasMahasiswaJob::dispatch(
                $institusi->id,  // int $institusiId
                '',              // string $syncProcessId (auto-generated in constructor)
                $semesterStart   // ?string $semester (filter semester)
            );

            return response()->json([
                'message' => 'Sync aktivitas mahasiswa job telah diqueue dengan sukses',
                'institusi' => [
                    'id' => $institusi->id,
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ],
                'parameters' => [
                    'semester' => $semesterStart,
                ],
                'info' => 'Job akan memproses data aktivitas mahasiswa (termasuk MBKM) dari Feeder untuk semester: '.($semesterStart ?? 'semua'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to queue sync aktivitas mahasiswa job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

/*
==========================================================================
                    📋 DAFTAR ENDPOINT SYNC CONTROLLER
==========================================================================

🔐 AUTHENTICATION: Semua endpoint memerlukan authentication dengan Sanctum
🏢 MIDDLEWARE: auth:sanctum, institusi (validasi user memiliki institusi)

📌 SYNC ENDPOINTS UNTUK INSTITUSI USER:

1. POST /api/sync/prodi
   Deskripsi: Sinkronisasi data program studi
   Parameters: - (none)
   Job: SyncProdiJob

2. POST /api/sync/dosen
   Deskripsi: Sinkronisasi data dosen
   Parameters: - (none)
   Job: SyncDosenJob

3. POST /api/sync/mahasiswa
   Deskripsi: Sinkronisasi data mahasiswa
   Parameters:
   - semester_start (optional): Semester mulai (format: YYYYS, contoh: 20241)
   - semester_end (optional): Semester akhir (format: YYYYS, contoh: 20242)
   - batch_size (optional): Ukuran batch processing (default: 100)
   - max_records (optional): Maksimal records yang diproses (default: 0 = unlimited)
   Job: SyncMahasiswaJob

4. POST /api/sync/akademik-mahasiswa
   Deskripsi: Sinkronisasi data akademik/perkuliahan mahasiswa
   Parameters:
   - semester_start (optional): Semester mulai (format: YYYYS, contoh: 20241)
   - semester_end (optional): Semester akhir (format: YYYYS, contoh: 20242)
   - batch_size (optional): Ukuran batch processing (default: 100)
   - max_records (optional): Maksimal records yang diproses (default: 0 = unlimited)
   Job: SyncAkademikMahasiswaJob

5. POST /api/sync/bimbingan-ta
   Deskripsi: Sinkronisasi data bimbingan tugas akhir
   Parameters:
   - batch_size (optional): Ukuran batch processing (default: 100)
   - max_records (optional): Maksimal records yang diproses (default: 0 = unlimited)
   Job: SyncBimbinganTaJob

6. POST /api/sync/dosen-akreditasi
   Deskripsi: Sinkronisasi data dosen untuk akreditasi
   Parameters:
   - batch_size (optional): Ukuran batch processing (default: 100)
   - max_records (optional): Maksimal records yang diproses (default: 0 = unlimited)
   Job: SyncDosenAkreditasiJob

7. POST /api/sync/lulusan
   Deskripsi: Sinkronisasi data lulusan mahasiswa
   Parameters:
   - tahun_lulus (optional): Tahun kelulusan (format: YYYY)
   - batch_size (optional): Ukuran batch processing (default: 100)
   - max_records (optional): Maksimal records yang diproses (default: 0 = unlimited)
   Job: SyncLulusanJob

8. POST /api/sync/prestasi
   Deskripsi: Sinkronisasi data prestasi mahasiswa
   Parameters:
   - tahun_prestasi (optional): Tahun prestasi (format: YYYY)
   - batch_size (optional): Ukuran batch processing (default: 100)
   - max_records (optional): Maksimal records yang diproses (default: 0 = unlimited)
   Job: SyncPrestasiMahasiswaJob

9. POST /api/sync/aktivitas-mahasiswa
   Deskripsi: Sinkronisasi data aktivitas mahasiswa (termasuk MBKM)
   Parameters:
   - semester_start (optional): Semester mulai (format: YYYYS, contoh: 20241)
   - semester_end (optional): Semester akhir (format: YYYYS, contoh: 20242)
   Job: SyncAktivitasMahasiswaJob

📌 SYNC ENDPOINTS UNTUK SEMUA INSTITUSI (ADMIN):

10. POST /api/sync/all-prodi
    Deskripsi: Sinkronisasi data prodi untuk semua institusi
    Parameters: - (none)
    Job: SyncProdiJob (untuk setiap institusi)

11. POST /api/sync/prodi/{slug}
    Deskripsi: Sinkronisasi data prodi untuk institusi tertentu berdasarkan slug
    Parameters:
    - slug (required): Slug institusi (path parameter)
    Job: SyncProdiJob

==========================================================================
                            📝 CATATAN PENTING
==========================================================================

🎯 FORMAT SEMESTER:
   - Format: YYYYS (Y=tahun, S=semester)
   - Contoh: 20241 (semester 1 tahun 2024), 20242 (semester 2 tahun 2024)

⚡ BATCH PROCESSING:
   - Semua job menggunakan batch processing untuk performa optimal
   - Default batch_size: 100 records per batch
   - Dapat disesuaikan sesuai kebutuhan server

🔄 QUEUE SYSTEM:
   - Semua sync menggunakan Laravel Queue Jobs
   - Jobs berjalan asynchronous untuk performa yang lebih baik
   - Monitor status jobs melalui log atau queue dashboard

🛡️ ERROR HANDLING:
   - Comprehensive error handling dan logging
   - Transaction safety untuk data integrity
   - Individual record failure tidak mengganggu batch lainnya

📊 MONITORING:
   - Semua aktivitas sync dicatat dalam log aplikasi
   - Statistik sync (created/updated/skipped/errors) tersedia di log
   - Progress tracking untuk monitoring job yang sedang berjalan

==========================================================================
*/
