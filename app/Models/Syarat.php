<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Syarat extends Model
{
    protected $table = 'syarat';

    public const JENIS = ['angka' => 'Angka minimal', 'link' => 'Link berkas', 'centang' => 'Centang'];

    protected $fillable = ['tahap_id', 'nama', 'jenis', 'nilai_minimal', 'keterangan', 'wajib', 'urutan'];

    protected $casts = ['wajib' => 'boolean', 'nilai_minimal' => 'float'];

    public function tahap(): BelongsTo
    {
        return $this->belongsTo(Tahap::class);
    }

    /** Apakah nilai yang diisi mahasiswa memenuhi syarat ini (pengecekan otomatis). */
    public function terpenuhiOleh(?string $nilai): bool
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '') {
            return false;
        }

        return match ($this->jenis) {
            'angka' => is_numeric(str_replace(',', '.', $nilai))
                && (float) str_replace(',', '.', $nilai) >= (float) ($this->nilai_minimal ?? 0),
            'link' => (bool) filter_var($nilai, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $nilai),
            'centang' => $nilai === '1',
            default => false,
        };
    }
}
