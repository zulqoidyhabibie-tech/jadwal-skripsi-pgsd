<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\JadwalUjian;
use App\Models\Skripsi;
use Illuminate\Support\Collection;

/**
 * Menghitung beban dosen dan menyarankan pembimbing.
 * Kuota 0 berarti tidak dibatasi.
 */
class PembagianDosen
{
    /** @return array<int, array{p1:int,p2:int}> beban bimbingan aktif per dosen */
    public function bebanBimbingan(): array
    {
        $beban = [];
        $aktif = Skripsi::where('status', 'disetujui')->get(['pembimbing_1_id', 'pembimbing_2_id']);
        foreach ($aktif as $s) {
            if ($s->pembimbing_1_id) {
                $beban[$s->pembimbing_1_id]['p1'] = ($beban[$s->pembimbing_1_id]['p1'] ?? 0) + 1;
            }
            if ($s->pembimbing_2_id) {
                $beban[$s->pembimbing_2_id]['p2'] = ($beban[$s->pembimbing_2_id]['p2'] ?? 0) + 1;
            }
        }

        return $beban;
    }

    /**
     * Daftar dosen yang boleh menjadi pembimbing ke-$ke, diurutkan dari beban paling ringan.
     * Tiap item: dosen, beban, kuota, penuh.
     */
    public function kandidatPembimbing(int $ke, ?array $beban = null): Collection
    {
        $beban ??= $this->bebanBimbingan();
        $kolomBoleh = $ke === 1 ? 'boleh_pembimbing_1' : 'boleh_pembimbing_2';
        $kolomKuota = $ke === 1 ? 'kuota_pembimbing_1' : 'kuota_pembimbing_2';
        $kunci = $ke === 1 ? 'p1' : 'p2';

        return Dosen::where('aktif', true)->where($kolomBoleh, true)->orderBy('nama')->get()
            ->map(function (Dosen $d) use ($beban, $kolomKuota, $kunci) {
                $jumlah = $beban[$d->id][$kunci] ?? 0;
                $kuota = (int) $d->{$kolomKuota};

                return [
                    'dosen' => $d,
                    'beban' => $jumlah,
                    'kuota' => $kuota,
                    'penuh' => $kuota > 0 && $jumlah >= $kuota,
                    'rasio' => $kuota > 0 ? $jumlah / $kuota : $jumlah,
                ];
            })
            ->sortBy([['penuh', 'asc'], ['rasio', 'asc'], ['beban', 'asc']])
            ->values();
    }

    /** Saran pasangan pembimbing: dua dosen berbeda dengan beban paling ringan. */
    public function saranPembimbing(): array
    {
        $beban = $this->bebanBimbingan();
        $p1 = $this->kandidatPembimbing(1, $beban)->firstWhere('penuh', false);
        $p2 = $this->kandidatPembimbing(2, $beban)
            ->first(fn ($k) => ! $k['penuh'] && $k['dosen']->id !== ($p1['dosen']->id ?? null));

        return [$p1['dosen'] ?? null, $p2['dosen'] ?? null];
    }

    /** @return array<int, array{u1:int,u2:int}> jumlah tugas menguji per dosen pada periode ini */
    public function bebanPenguji(?int $periodeId = null): array
    {
        $beban = [];
        $q = JadwalUjian::query();
        if ($periodeId) {
            $q->whereHas('sesi', fn ($w) => $w->where('periode_id', $periodeId));
        }
        foreach ($q->get(['penguji_1_id', 'penguji_2_id']) as $j) {
            $beban[$j->penguji_1_id]['u1'] = ($beban[$j->penguji_1_id]['u1'] ?? 0) + 1;
            $beban[$j->penguji_2_id]['u2'] = ($beban[$j->penguji_2_id]['u2'] ?? 0) + 1;
        }

        return $beban;
    }

    /**
     * Dosen yang boleh menguji, tanpa pembimbing mahasiswa itu, diurutkan dari beban paling ringan.
     *
     * @param  int[]  $kecuali  id dosen yang tidak boleh (pembimbing)
     */
    public function kandidatPenguji(int $ke, array $kecuali = [], ?array $beban = null): Collection
    {
        $beban ??= $this->bebanPenguji();
        $kolomKuota = $ke === 1 ? 'kuota_penguji_1' : 'kuota_penguji_2';
        $kunci = $ke === 1 ? 'u1' : 'u2';

        return Dosen::where('aktif', true)->where('boleh_penguji', true)
            ->whereNotIn('id', array_filter($kecuali))->orderBy('nama')->get()
            ->map(function (Dosen $d) use ($beban, $kolomKuota, $kunci) {
                $jumlah = $beban[$d->id][$kunci] ?? 0;
                $kuota = (int) $d->{$kolomKuota};

                return [
                    'dosen' => $d,
                    'beban' => $jumlah,
                    'kuota' => $kuota,
                    'penuh' => $kuota > 0 && $jumlah >= $kuota,
                    'rasio' => $kuota > 0 ? $jumlah / $kuota : $jumlah,
                ];
            })
            ->sortBy([['penuh', 'asc'], ['rasio', 'asc'], ['beban', 'asc']])
            ->values();
    }

    /** Saran penguji 1 dan 2 yang berbeda dan bukan pembimbing. */
    public function saranPenguji(array $pembimbing): array
    {
        $beban = $this->bebanPenguji();
        $u1 = $this->kandidatPenguji(1, $pembimbing, $beban)->firstWhere('penuh', false);
        $u2 = $this->kandidatPenguji(2, $pembimbing, $beban)
            ->first(fn ($k) => ! $k['penuh'] && $k['dosen']->id !== ($u1['dosen']->id ?? null));

        return [$u1['dosen'] ?? null, $u2['dosen'] ?? null];
    }
}
