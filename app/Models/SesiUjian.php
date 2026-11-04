<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiUjian extends Model
{
    protected $table = 'sesi_ujian';

    protected $fillable = ['kode', 'tahap_id', 'periode_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'ruang_id', 'kapasitas', 'terbit'];

    protected $casts = ['tanggal' => 'date', 'terbit' => 'boolean'];

    public function tahap(): BelongsTo
    {
        return $this->belongsTo(Tahap::class);
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class);
    }

    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalUjian::class)->orderBy('id');
    }

    public function sisaSlot(): int
    {
        return max(0, $this->kapasitas - $this->jadwal->count());
    }

    public function hari(): string
    {
        return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$this->tanggal->dayOfWeek];
    }

    public function waktu(): string
    {
        return substr($this->jam_mulai, 0, 5).'–'.substr($this->jam_selesai, 0, 5);
    }

    /** Teks tanggal lengkap, contoh: Senin, 16-11-2026. */
    public function tanggalLengkap(): string
    {
        return $this->hari().', '.$this->tanggal->format('d-m-Y');
    }
}
