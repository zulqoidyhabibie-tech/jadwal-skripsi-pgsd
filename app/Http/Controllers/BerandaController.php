<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('mahasiswa')) {
            return redirect()->route('mhs.beranda');
        }
        if ($user->hasRole('dosen')) {
            return redirect()->route('dosen.beranda');
        }

        return view('beranda', [
            'menungguJudul' => Pendaftaran::whereHas('tahap', fn ($q) => $q->where('kode', 'judul'))->where('status', 'diajukan')->count(),
            'menungguVerifikasi' => Pendaftaran::whereHas('tahap', fn ($q) => $q->where('kode', '!=', 'judul'))->where('status', 'diajukan')->count(),
            'akunBaru' => User::where('is_active', false)->count(),
            'jumlahMahasiswa' => Mahasiswa::count(),
            'jumlahDosen' => Dosen::where('aktif', true)->count(),
            'skripsiAktif' => Skripsi::where('status', 'disetujui')->count(),
        ]);
    }
}
