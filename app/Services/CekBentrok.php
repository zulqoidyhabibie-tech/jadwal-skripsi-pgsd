<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\JadwalUjian;
use App\Models\SesiUjian;

/**
 * Mencari bentrok jadwal: ruang yang sama atau dosen yang sama
 * pada sesi lain di tanggal yang sama dengan jam yang beririsan.
 */
class CekBentrok
{
    /** Sesi lain yang waktunya beririsan dengan $sesi. */
    public function sesiBeririsan(SesiUjian $sesi)
    {
        return SesiUjian::with('ruang', 'jadwal.pendaftaran.mahasiswa.skripsi')
            ->whereDate('tanggal', $sesi->tanggal)
            ->where('id', '!=', $sesi->id)
            ->where('jam_mulai', '<', $sesi->jam_selesai)
            ->where('jam_selesai', '>', $sesi->jam_mulai)
            ->get();
    }

    /** @return string[] pesan bentrok ruang untuk sesi ini */
    public function bentrokRuang(SesiUjian $sesi): array
    {
        return $this->sesiBeririsan($sesi)
            ->where('ruang_id', $sesi->ruang_id)
            ->map(fn ($s) => "Ruang {$s->ruang->nama} sudah dipakai sesi {$s->kode} pada jam {$s->waktu()}.")
            ->values()->all();
    }

    /**
     * @param  int[]  $dosenIds  dosen yang akan hadir di slot baru
     * @return string[] pesan bentrok dosen
     */
    public function bentrokDosen(SesiUjian $sesi, array $dosenIds, ?int $kecualiJadwalId = null): array
    {
        $dosenIds = array_unique(array_filter($dosenIds));
        $pesan = [];
        foreach ($this->sesiBeririsan($sesi) as $lain) {
            foreach ($lain->jadwal as $j) {
                if ($j->id === $kecualiJadwalId) {
                    continue;
                }
                foreach (array_intersect($dosenIds, $j->dosenHadir()) as $id) {
                    $nama = Dosen::find($id)?->nama ?? 'Dosen';
                    $pesan[] = "{$nama} sudah bertugas di sesi {$lain->kode} ({$lain->tanggalLengkap()}, {$lain->waktu()}).";
                }
            }
        }

        return array_values(array_unique($pesan));
    }

    /** Semua bentrok dalam satu sesi (untuk peringatan di daftar jadwal). */
    public function semuaBentrok(SesiUjian $sesi): array
    {
        $dosen = $sesi->jadwal->flatMap(fn (JadwalUjian $j) => $j->dosenHadir())->all();

        return array_merge($this->bentrokRuang($sesi), $this->bentrokDosen($sesi, $dosen));
    }
}
