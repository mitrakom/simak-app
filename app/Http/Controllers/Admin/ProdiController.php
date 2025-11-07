<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institusi;
use Illuminate\View\View;

class ProdiController extends Controller
{
    public function index(Institusi $institusi): View
    {
        return view('admin.prodi.index', compact('institusi'));
    }
}
