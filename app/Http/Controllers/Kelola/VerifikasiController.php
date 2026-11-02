<?php

namespace App\Http\Controllers\Kelola;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Services\AlurSkripsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifikasiController extends Controller
{
    public function __construct(private AlurSkripsi $alur) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'diajukan');
        $daftar = Pendaftaran::with('mahasiswa.skripsi', 'tahap')
            ->whereHas('tahap', fn ($q) => $q->where('kode', '!=', 'judul'))
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest('diajukan_pada')->get();

        return view('kelola.verifikasi-index', compact('daftar', 'status'));
    }

    public function show(Pendaftaran $pendaftaran)
    {
        return view('kelola.verifikasi-show', [
            'p' => $pendaftaran->load('mahasiswa.skripsi.pembimbing1', 'mahasiswa.skripsi.pembimbing2', 'tahap'),
            'cek' => $this->alur->cekSyarat($pendaftaran),
        ]);
    }

    public function putuskan(Request $request, Pendaftaran $pendaftaran)
    {
        abort_unless($pendaftaran->status === 'diajukan', 403, 'Pendaftaran ini tidak sedang menunggu verifikasi.');
        $data = $request->validate([
            'status' => ['array'],
            'status.*' => ['in:diterima,ditolak'],
            'catatan' => ['array'],
            'catatan.*' => ['nullable', 'string', 'max:255'],
            'catatan_umum' => ['nullable', 'string', 'max:1000'],
        ]);

        $adaDitolak = false;
        DB::transaction(function () use ($pendaftaran, $data, &$adaDitolak) {
            foreach ($pendaftaran->isian as $i) {
                $st = $data['status'][$i->id] ?? 'diterima';
                $adaDitolak = $adaDitolak || $st === 'ditolak';
                $i->update(['status' => $st, 'catatan' => $data['catatan'][$i->id] ?? null]);
            }
            $pendaftaran->update($adaDitolak
                ? ['status' => 'ditolak', 'catatan' => $data['catatan_umum'] ?? 'Ada syarat yang perlu diperbaiki.']
                : ['status' => 'terverifikasi', 'catatan' => $data['catatan_umum'] ?? null, 'diverifikasi_pada' => now()]);
        });

        return redirect()->route('kelola.verifikasi.index')->with('ok', $adaDitolak
            ? "Pendaftaran {$pendaftaran->mahasiswa->nama} dikembalikan untuk diperbaiki."
            : "Pendaftaran {$pendaftaran->mahasiswa->nama} terverifikasi dan siap dijadwalkan.");
    }
}
