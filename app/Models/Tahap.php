<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tahap extends Model
{
    protected $table = 'tahap';

    protected $fillable = ['kode', 'nama', 'urutan'];

    public function syarat(): HasMany
    {
        return $this->hasMany(Syarat::class)->orderBy('urutan')->orderBy('id');
    }

    public function sebelumnya(): ?self
    {
        return static::where('urutan', '<', $this->urutan)->orderByDesc('urutan')->first();
    }
}
