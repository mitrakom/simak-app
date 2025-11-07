<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institusi;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Institusi $institusi): View
    {
        return view('admin.dashboard', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Display Peta Perjalanan Mahasiswa page.
     */
    public function petaPerjalanan(Institusi $institusi): View
    {
        return view('admin.analisis.peta-perjalanan', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Display Sebaran IPS Mahasiswa page.
     */
    public function sebaranIps(Institusi $institusi): View
    {
        return view('admin.analisis.sebaran-ips', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Display Monitoring Bimbingan TA page.
     */
    public function bimbinganTa(Institusi $institusi): View
    {
        return view('admin.monitoring.bimbingan-ta', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Display Kesiapan Akreditasi & IKU page.
     */
    public function akreditasiIku(Institusi $institusi): View
    {
        return view('admin.laporan.akreditasi-iku', [
            'institusi' => $institusi,
        ]);
    }

    /**
     * Display Status Pelaporan Prodi page.
     */
    public function statusProdi(Institusi $institusi): View
    {
        return view('admin.laporan.status-prodi', [
            'institusi' => $institusi,
        ]);
    }
}
