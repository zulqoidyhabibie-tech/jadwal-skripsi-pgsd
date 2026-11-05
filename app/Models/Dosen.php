<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    protected $table = 'dosen';

    protected $fillable = [
        'user_id', 'nama', 'nomor_identitas', 'jenis_identitas', 'bidang_keahlian',
        'boleh_pembimbing_1', 'boleh_pembimbing_2', 'boleh_penguji',
        'kuota_pembimbing_1', 'kuota_pembimbing_2', 'kuota_penguji_1', 'kuota_penguji_2', 'aktif',
    ];

    protected $casts = [
        'boleh_pembimbing_1' => 'boolean',
        'boleh_pembimbing_2' => 'boolean',
        'boleh_penguji' => 'boolean',
        'aktif' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bimbinganSebagaiP1(): HasMany
    {
        return $this->hasMany(Skripsi::class, 'pembimbing_1_id');
    }

    public function bimbinganSebagaiP2(): HasMany
    {
        return $this->hasMany(Skripsi::class, 'pembimbing_2_id');
    }
}
