<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstitusiResource;
use App\Models\Institusi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitusiController extends Controller
{
    /**
     * Display a listing of institusis.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Institusi::query();

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->string('search'));
        }

        // Filter by feeder configuration
        if ($request->boolean('has_feeder_config')) {
            $query->hasFeederConfig();
        }

        $institusis = $query->withCount(['users', 'prodis'])
            ->orderBy('nama')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'message' => 'Institusis retrieved successfully',
            'data' => InstitusiResource::collection($institusis->items()),
            'meta' => [
                'current_page' => $institusis->currentPage(),
                'last_page' => $institusis->lastPage(),
                'per_page' => $institusis->perPage(),
                'total' => $institusis->total(),
            ],
        ]);
    }

    /**
     * Display the specified institusi by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $institusi = Institusi::where('slug', $slug)
            ->withCount(['users', 'prodis'])
            ->firstOrFail();

        return response()->json([
            'message' => 'Institusi retrieved successfully',
            'data' => new InstitusiResource($institusi),
        ]);
    }

    /**
     * Validate if a slug exists.
     */
    public function validateSlug(string $slug): JsonResponse
    {
        $institusi = Institusi::where('slug', $slug)->first();

        if (! $institusi) {
            return response()->json([
                'message' => 'Kode institusi tidak ditemukan.',
                'valid' => false,
            ], 404);
        }

        return response()->json([
            'message' => 'Kode institusi valid.',
            'valid' => true,
            'data' => [
                'slug' => $institusi->slug,
                'nama' => $institusi->nama,
            ],
        ]);
    }

    /**
     * Get all institusis with minimal data for selection.
     */
    public function options(): JsonResponse
    {
        $institusis = Institusi::select('id', 'nama', 'slug')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'message' => 'Institusi options retrieved successfully',
            'data' => $institusis->map(function ($institusi) {
                return [
                    'value' => $institusi->id,
                    'label' => $institusi->nama,
                    'slug' => $institusi->slug,
                ];
            }),
        ]);
    }
}
