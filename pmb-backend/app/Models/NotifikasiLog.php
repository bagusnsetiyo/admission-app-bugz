<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model NotifikasiLog — log event in-app untuk modul JadwalHub
 */
class NotifikasiLog extends Model
{
    protected $table = 'notifikasi_log';

    protected $fillable = [
        'pendaftar_id',
        'jenis',
        'pesan',
        'dibaca',
    ];

    protected $casts = [
        'dibaca' => 'boolean',
    ];

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'pendaftar_id');
    }
}
