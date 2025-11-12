<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Institusi;
use Illuminate\View\View;

/**
 * Controller untuk halaman landing page multi-tenant.
 *
 * Setiap institusi memiliki landing page unik berdasarkan slug URL.
 * Route Model Binding otomatis resolve Institusi dari parameter {institusi:slug}.
 */
class LandingPageController extends Controller
{
    /**
     * Tampilkan landing page untuk institusi tertentu.
     *
     * @param  Institusi  $institusi  Instance yang sudah di-resolve oleh Route Model Binding
     */
    public function show(Institusi $institusi): View
    {
        // Data institusi sudah di-resolve oleh Route Model Binding
        // Jika slug tidak ditemukan, Laravel otomatis return 404

        return view('landing', [
            'institusi' => $institusi,
        ]);
    }
}
