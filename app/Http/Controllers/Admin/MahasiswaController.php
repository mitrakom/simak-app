<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institusi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MahasiswaController extends Controller
{
    public function index(Institusi $institusi): View
    {
        return view('admin.mahasiswa.index', compact('institusi'));
    }

    public function create(Institusi $institusi): View
    {
        return view('admin.mahasiswa.create', compact('institusi'));
    }

    public function show(Institusi $institusi, string $id): View
    {
        return view('admin.mahasiswa.show', compact('institusi', 'id'));
    }

    public function edit(Institusi $institusi, string $id): View
    {
        return view('admin.mahasiswa.edit', compact('institusi', 'id'));
    }
}
