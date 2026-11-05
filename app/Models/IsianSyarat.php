<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IsianSyarat extends Model
{
    protected $table = 'isian_syarat';

    protected $fillable = ['pendaftaran_id', 'syarat_id', 'nilai', 'status', 'catatan'];

    public function syarat(): BelongsTo
    {
        return $this->belongsTo(Syarat::class);
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
