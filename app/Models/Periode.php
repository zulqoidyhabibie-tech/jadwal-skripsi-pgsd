<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    protected $table = 'periode';

    protected $fillable = ['nama', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public static function aktif(): ?self
    {
        return static::where('aktif', true)->first();
    }
}
