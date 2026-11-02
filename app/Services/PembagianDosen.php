<?php

namespace App\Services;

use App\Models\Dosen;
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
}
