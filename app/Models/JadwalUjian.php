<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalUjian extends Model
{
    protected $table = 'jadwal_ujian';

    public const HASIL = ['lulus' => 'Lulus', 'lulus_revisi' => 'Lulus dengan revisi', 'mengulang' => 'Mengulang'];

    protected $fillable = ['sesi_ujian_id', 'pendaftaran_id', 'penguji_1_id', 'penguji_2_id', 'hasil', 'catatan_hasil'];

    public function sesi(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id');
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function penguji1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'penguji_1_id');
    }

    public function penguji2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'penguji_2_id');
    }

    /** Semua dosen yang harus hadir: pembimbing 1/2 dan penguji 1/2. */
    public function dosenHadir(): array
    {
        $sk = $this->pendaftaran->mahasiswa->skripsi;

        return array_values(array_filter([
            $sk?->pembimbing_1_id, $sk?->pembimbing_2_id, $this->penguji_1_id, $this->penguji_2_id,
        ]));
    }
}
