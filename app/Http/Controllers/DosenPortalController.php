<?php

namespace App\Http\Controllers;

use App\Models\JadwalUjian;
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

        $jadwal = collect();
        if ($dosen) {
            $idSkripsi = $bimbingan->pluck('mahasiswa_id');
            $jadwal = JadwalUjian::with('sesi.ruang', 'sesi.tahap', 'pendaftaran.mahasiswa')
                ->whereHas('sesi', fn ($q) => $q->where('terbit', true)->whereDate('tanggal', '>=', now()->subDays(7)))
                ->where(fn ($q) => $q->where('penguji_1_id', $dosen->id)->orWhere('penguji_2_id', $dosen->id)
                    ->orWhereHas('pendaftaran', fn ($w) => $w->whereIn('mahasiswa_id', $idSkripsi)))
                ->get()
                ->sortBy(fn ($j) => $j->sesi->tanggal->format('Y-m-d').$j->sesi->jam_mulai)
                ->map(function ($j) use ($dosen, $bimbingan) {
                    $sk = $bimbingan->firstWhere('mahasiswa_id', $j->pendaftaran->mahasiswa_id);
                    $j->peranSaya = match (true) {
                        $j->penguji_1_id === $dosen->id => 'Penguji 1',
                        $j->penguji_2_id === $dosen->id => 'Penguji 2',
                        $sk?->pembimbing_1_id === $dosen->id => 'Pembimbing 1',
                        default => 'Pembimbing 2',
                    };

                    return $j;
                });
        }

        return view('dosen.beranda', compact('dosen', 'bimbingan', 'jadwal'));
    }
}
