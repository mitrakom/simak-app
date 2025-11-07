<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeederClient;
use App\Models\Institusi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeederDataController extends Controller
{
    /**
     * Constructor dengan Dependency Injection
     */
    public function __construct(
        protected FeederClient $feederClient
    ) {}

    /**
     * Get dosen data untuk institusi tertentu berdasarkan slug
     */
    public function getDosenByInstitusi(Request $request, string $slug): JsonResponse
    {
        try {
            // Validasi institusi exists
            $institusi = Institusi::where('slug', $slug)->first();
            if (!$institusi) {
                return response()->json([
                    'message' => 'Institusi not found',
                    'error' => 'INSTITUSI_NOT_FOUND'
                ], 404);
            }

            // Check if institusi has feeder configuration
            if (!$institusi->hasFeederConfig()) {
                return response()->json([
                    'message' => 'Institusi does not have feeder configuration',
                    'error' => 'FEEDER_CONFIG_MISSING'
                ], 400);
            }

            // Build filter for specific institusi
            $filter = [];

            // Add institusi-specific filter if needed
            // $filter['id_sp'] = "= '" . $institusi->feeder_sp_id . "'"; // contoh jika ada ID SP

            // Add search filter if provided
            if ($request->has('search')) {
                $search = $request->string('search');
                $filter['nm_dosen'] = "like '%" . $search . "%'";
            }

            // Add status filter
            if ($request->has('status')) {
                $status = $request->string('status');
                $filter['a_aktif'] = "= '" . $status . "'";
            }

            $limit = $request->integer('per_page', 10);
            $offset = ($request->integer('page', 1) - 1) * $limit;

            // Fetch data from Feeder API
            $response = $this->feederClient->fetch(
                'GetListDosen',
                $filter,
                'nm_dosen ASC',
                $limit,
                $offset
            );

            if (!$response) {
                return response()->json([
                    'message' => 'Failed to connect to Feeder API',
                    'error' => 'FEEDER_CONNECTION_FAILED'
                ], 500);
            }

            if ($response['error_code'] != 0) {
                Log::error('Feeder API Error when fetching dosen', [
                    'institusi_slug' => $slug,
                    'error_code' => $response['error_code'],
                    'error_desc' => $response['error_desc'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'message' => 'Feeder API error',
                    'error' => $response['error_desc'] ?? 'Unknown error',
                    'error_code' => $response['error_code']
                ], 400);
            }

            $data = $response['data'] ?? [];

            return response()->json([
                'message' => 'Dosen data retrieved successfully',
                'data' => $data,
                'meta' => [
                    'institusi' => [
                        'slug' => $institusi->slug,
                        'nama' => $institusi->nama,
                    ],
                    'pagination' => [
                        'page' => $request->integer('page', 1),
                        'per_page' => $limit,
                        'total' => count($data),
                    ],
                    'filters_applied' => $filter
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching dosen data', [
                'institusi_slug' => $slug,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error occurred'
            ], 500);
        }
    }

    /**
     * Get mahasiswa data untuk institusi tertentu berdasarkan slug
     */
    public function getMahasiswaByInstitusi(Request $request, string $slug): JsonResponse
    {
        try {
            $institusi = Institusi::where('slug', $slug)->first();
            if (!$institusi) {
                return response()->json([
                    'message' => 'Institusi not found'
                ], 404);
            }

            if (!$institusi->hasFeederConfig()) {
                return response()->json([
                    'message' => 'Institusi does not have feeder configuration'
                ], 400);
            }

            $filter = [];

            // Search by name
            if ($request->has('search')) {
                $search = $request->string('search');
                $filter['nm_pd'] = "like '%" . $search . "%'";
            }

            // Filter by program studi
            if ($request->has('prodi_id')) {
                $prodiId = $request->string('prodi_id');
                $filter['id_sms'] = "= '" . $prodiId . "'";
            }

            // Filter by angkatan
            if ($request->has('angkatan')) {
                $angkatan = $request->string('angkatan');
                $filter['id_periode_masuk'] = "= '" . $angkatan . "'";
            }

            // Filter by status mahasiswa
            if ($request->has('status')) {
                $status = $request->string('status');
                $filter['id_stat_mhs'] = "= '" . $status . "'";
            }

            $limit = $request->integer('per_page', 10);
            $offset = ($request->integer('page', 1) - 1) * $limit;

            $response = $this->feederClient->fetch(
                'GetListMahasiswa',
                $filter,
                'nm_pd ASC',
                $limit,
                $offset
            );

            if (!$response || $response['error_code'] != 0) {
                return response()->json([
                    'message' => 'Failed to fetch mahasiswa data',
                    'error' => $response['error_desc'] ?? 'API Error'
                ], 400);
            }

            return response()->json([
                'message' => 'Mahasiswa data retrieved successfully',
                'data' => $response['data'] ?? [],
                'meta' => [
                    'institusi' => [
                        'slug' => $institusi->slug,
                        'nama' => $institusi->nama,
                    ],
                    'pagination' => [
                        'page' => $request->integer('page', 1),
                        'per_page' => $limit,
                        'total' => count($response['data'] ?? []),
                    ],
                    'filters_applied' => $filter
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching mahasiswa data', [
                'institusi_slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch mahasiswa data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get program studi data untuk institusi tertentu
     */
    public function getProdiByInstitusi(Request $request, string $slug): JsonResponse
    {
        try {
            $institusi = Institusi::where('slug', $slug)->first();
            if (!$institusi || !$institusi->hasFeederConfig()) {
                return response()->json(['message' => 'Institusi not found or no feeder config'], 404);
            }

            $filter = [];

            if ($request->has('search')) {
                $search = $request->string('search');
                $filter['nm_prodi'] = "like '%" . $search . "%'";
            }

            // Filter by jenjang
            if ($request->has('jenjang')) {
                $jenjang = $request->string('jenjang');
                $filter['id_jenj_didik'] = "= '" . $jenjang . "'";
            }

            $response = $this->feederClient->fetch(
                'GetProdi',
                $filter,
                'nm_prodi ASC',
                $request->integer('per_page', 20),
                ($request->integer('page', 1) - 1) * $request->integer('per_page', 20)
            );

            if (!$response || $response['error_code'] != 0) {
                return response()->json([
                    'message' => 'Failed to fetch prodi data',
                    'error' => $response['error_desc'] ?? 'API Error'
                ], 400);
            }

            return response()->json([
                'message' => 'Program studi data retrieved successfully',
                'data' => $response['data'] ?? [],
                'meta' => [
                    'institusi' => [
                        'slug' => $institusi->slug,
                        'nama' => $institusi->nama,
                    ],
                    'total' => count($response['data'] ?? [])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch prodi data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync/sinkronisasi data dari Feeder ke database lokal
     */
    public function syncData(Request $request, string $slug): JsonResponse
    {
        try {
            $institusi = Institusi::where('slug', $slug)->first();
            if (!$institusi || !$institusi->hasFeederConfig()) {
                return response()->json(['message' => 'Institusi not found or no feeder config'], 404);
            }

            $dataType = $request->string('type', 'dosen'); // dosen, mahasiswa, prodi
            $limit = $request->integer('limit', 50);

            // Determine the action based on data type
            $actionMap = [
                'dosen' => 'GetListDosen',
                'mahasiswa' => 'GetListMahasiswa',
                'prodi' => 'GetProdi'
            ];

            $action = $actionMap[$dataType] ?? 'GetListDosen';

            $response = $this->feederClient->fetch($action, [], 'id ASC', $limit, 0);

            if (!$response || $response['error_code'] != 0) {
                return response()->json([
                    'message' => 'Failed to sync data',
                    'error' => $response['error_desc'] ?? 'API Error'
                ], 400);
            }

            // Here you would implement the logic to save data to local database
            // For example, save to respective models based on data type
            $syncedCount = count($response['data'] ?? []);

            Log::info('Data sync completed', [
                'institusi_slug' => $slug,
                'data_type' => $dataType,
                'synced_count' => $syncedCount
            ]);

            return response()->json([
                'message' => 'Data synchronization completed',
                'data_type' => $dataType,
                'synced_count' => $syncedCount,
                'institusi' => [
                    'slug' => $institusi->slug,
                    'nama' => $institusi->nama,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error during data sync', [
                'institusi_slug' => $slug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Sync failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
