<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model PenugasanJadwal — penugasan pendaftar ke slot jadwal
 */
class PenugasanJadwal extends Model
{
    const STATUS_TERJADWAL = 'terjadwal';
    const STATUS_HADIR = 'hadir';
    const STATUS_TIDAK_HADIR = 'tidak_hadir';
    const STATUS_BATAL = 'batal';

    protected $table = 'penugasan_jadwal';

    protected $fillable = [
        'jadwal_tes_id',
        'pendaftar_id',
        'checkin_token',
        'status',
        'ditugaskan_oleh',
        'ditugaskan_at',
    ];

    protected $casts = [
        'ditugaskan_at' => 'datetime',
    ];

    public function jadwalTes(): BelongsTo
    {
        return $this->belongsTo(JadwalTes::class, 'jadwal_tes_id');
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'pendaftar_id');
    }

    public function kehadiran(): HasOne
    {
        return $this->hasOne(KehadiranTes::class, 'penugasan_jadwal_id');
    }

    public function permintaanReschedule(): HasMany
    {
        return $this->hasMany(PermintaanReschedule::class, 'penugasan_jadwal_id');
    }

    public function ditugaskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }
}
