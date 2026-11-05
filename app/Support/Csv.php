<?php

namespace App\Support;

class Csv
{
    /** Baca CSV (pemisah koma atau titik koma) menjadi array asosiatif berdasarkan baris judul. */
    public static function baca(string $path): array
    {
        $isi = file_get_contents($path);
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi); // buang BOM dari Excel
        $baris = preg_split('/\r\n|\n|\r/', trim($isi));
        if (! $baris) {
            return [];
        }
        $pemisah = substr_count($baris[0], ';') > substr_count($baris[0], ',') ? ';' : ',';
        $judul = array_map(fn ($h) => strtolower(trim(str_replace(' ', '_', $h))), str_getcsv(array_shift($baris), $pemisah));
        $hasil = [];
        foreach ($baris as $b) {
            if (trim($b) === '') {
                continue;
            }
            $kolom = str_getcsv($b, $pemisah);
            $hasil[] = array_combine($judul, array_pad(array_slice($kolom, 0, count($judul)), count($judul), ''));
        }

        return $hasil;
    }

    public static function ya(?string $v): bool
    {
        return in_array(strtolower(trim((string) $v)), ['1', 'ya', 'y', 'yes', 'true', 'v', 'x'], true);
    }
}
