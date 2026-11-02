<?php

namespace App\Http\Controllers;

use App\Models\Skripsi;
use Illuminate\Http\Request;

class DosenPortalController extends Controller
{
    public function beranda(Request $request)
    {
        $dosen = $request->user()->dosen;
        $bimbingan = $dosen
            ? Skripsi::with('mahasiswa')->where('status', 'disetujui')
                ->where(fn ($q) => $q->where('pembimbing_1_id', $dosen->id)->orWhere('pembimbing_2_id', $dosen->id))
                ->get()
            : collect();

        return view('dosen.beranda', compact('dosen', 'bimbingan'));
    }
}
