<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeederClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Example controller using the new middleware approach
 *
 * Benefits:
 * 1. No authentication validation in constructor/methods
 * 2. Clean separation of concerns
 * 3. Middleware handles all FeederClient prerequisites
 * 4. Service focus on business logic only
 */
class DosenController extends Controller
{
    public function __construct(
        protected FeederClient $feederClient
    ) {}

    /**
     * Get list of dosen for authenticated user's institusi
     * Protected by: auth:sanctum + feeder.ready middleware
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Middleware sudah memastikan user valid dan institusi ready
            // Tidak perlu validasi authentication/institusi lagi
            $this->feederClient->setInstitusiFromRequest($request);

            $filter = [];
            if ($request->has('nama')) {
                $filter['nm_dosen'] = "like '%".$request->string('nama')."%'";
            }

            $response = $this->feederClient->fetch(
                'GetListDosen',
                $filter,
                'nm_dosen ASC',
                $request->integer('per_page', 10),
                ($request->integer('page', 1) - 1) * $request->integer('per_page', 10)
            );

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                return response()->json([
                    'message' => 'Failed to fetch dosen data',
                    'error' => $response['error_desc'] ?? 'Unknown error',
                ], 400);
            }

            return response()->json([
                'message' => 'Dosen data retrieved successfully',
                'institusi' => $this->feederClient->getCurrentInstitusi()->only(['slug', 'nama']),
                'data' => $response['data'] ?? [],
                'pagination' => [
                    'page' => $request->integer('page', 1),
                    'per_page' => $request->integer('per_page', 10),
                    'total' => count($response['data'] ?? []),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Get specific dosen by ID
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $this->feederClient->setInstitusiFromRequest($request);

            $response = $this->feederClient->fetch(
                'GetListDosen',
                ['id_dosen' => "= '$id'"]
            );

            if (! $response || ($response['error_code'] ?? 0) != 0) {
                return response()->json([
                    'message' => 'Failed to fetch dosen data',
                    'error' => $response['error_desc'] ?? 'Unknown error',
                ], 400);
            }

            $dosen = $response['data'][0] ?? null;
            if (! $dosen) {
                return response()->json([
                    'message' => 'Dosen not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Dosen data retrieved successfully',
                'data' => $dosen,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }
}
