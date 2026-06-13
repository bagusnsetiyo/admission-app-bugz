<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model KehadiranTes — record check-in operator per penugasan
 */
class KehadiranTes extends Model
{
    const STATUS_HADIR = 'hadir';
    const STATUS_TIDAK_HADIR = 'tidak_hadir';

    protected $table = 'kehadiran_tes';

    protected $fillable = [
        'penugasan_jadwal_id',
        'status',
        'checked_in_at',
        'operator_keterangan',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function penugasanJadwal(): BelongsTo
    {
        return $this->belongsTo(PenugasanJadwal::class, 'penugasan_jadwal_id');
    }
}
