<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Tahap;

/** Aturan kapan sebuah tahap terbuka untuk mahasiswa. */
class AlurSkripsi
{
    public function pendaftaranTerakhir(Mahasiswa $mhs, Tahap $tahap): ?Pendaftaran
    {
        return $mhs->pendaftaran()->where('tahap_id', $tahap->id)->latest('id')->first();
    }

    public function tahapLulus(Mahasiswa $mhs, Tahap $tahap): bool
    {
        return $mhs->pendaftaran()->where('tahap_id', $tahap->id)->where('status', 'lulus')->exists();
    }

    public function tahapTerbuka(Mahasiswa $mhs, Tahap $tahap): bool
    {
        $sebelumnya = $tahap->sebelumnya();

        return $sebelumnya === null || $this->tahapLulus($mhs, $sebelumnya);
    }

    /** Ambil pendaftaran yang sedang berjalan, atau buat draf baru bila tahap terbuka. */
    public function pendaftaranAktif(Mahasiswa $mhs, Tahap $tahap, bool $buat = false): ?Pendaftaran
    {
        $p = $this->pendaftaranTerakhir($mhs, $tahap);
        if ($p && $p->status !== 'mengulang') {
            return $p;
        }
        if (! $buat || ! $this->tahapTerbuka($mhs, $tahap)) {
            return $p;
        }

        return Pendaftaran::create([
            'mahasiswa_id' => $mhs->id,
            'tahap_id' => $tahap->id,
            'periode_id' => Periode::aktif()?->id,
            'status' => 'draf',
        ]);
    }

    /** @return array{lengkap:bool, hasil: array<int, array{syarat:\App\Models\Syarat, isian:?\App\Models\IsianSyarat, ok:bool}>} */
    public function cekSyarat(Pendaftaran $p): array
    {
        $isian = $p->isian()->get()->keyBy('syarat_id');
        $hasil = [];
        $lengkap = true;
        foreach ($p->tahap->syarat as $s) {
            $i = $isian->get($s->id);
            $ok = $s->terpenuhiOleh($i?->nilai);
            if ($s->wajib && ! $ok) {
                $lengkap = false;
            }
            $hasil[] = ['syarat' => $s, 'isian' => $i, 'ok' => $ok];
        }

        return ['lengkap' => $lengkap, 'hasil' => $hasil];
    }
}
