<?php

namespace App\Http\Controllers\Kelola;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Services\PembagianDosen;

class RekapDosenController extends Controller
{
    public function __invoke(PembagianDosen $pembagian)
    {
        $beban = $pembagian->bebanBimbingan();
        $dosen = Dosen::where('aktif', true)->orderBy('nama')->get();

        return view('kelola.rekap-dosen', compact('dosen', 'beban'));
    }
}
