<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendaftaran extends Model
{
    protected $table = 'pendaftaran';

    public const STATUS = [
        'draf' => 'Mengisi syarat',
        'diajukan' => 'Menunggu verifikasi',
        'ditolak' => 'Perlu perbaikan',
        'terverifikasi' => 'Terverifikasi',
        'dijadwalkan' => 'Dijadwalkan',
        'lulus' => 'Lulus',
        'mengulang' => 'Mengulang',
    ];

    protected $fillable = ['mahasiswa_id', 'tahap_id', 'periode_id', 'status', 'catatan', 'diajukan_pada', 'diverifikasi_pada'];

    protected $casts = ['diajukan_pada' => 'datetime', 'diverifikasi_pada' => 'datetime'];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function tahap(): BelongsTo
    {
        return $this->belongsTo(Tahap::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function isian(): HasMany
    {
        return $this->hasMany(IsianSyarat::class);
    }

    public function jadwal(): HasOne
    {
        return $this->hasOne(JadwalUjian::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Bisa diubah mahasiswa hanya saat masih draf atau dikembalikan. */
    public function bisaDiubah(): bool
    {
        return in_array($this->status, ['draf', 'ditolak'], true);
    }
}
