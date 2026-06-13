<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PermintaanReschedule — antrian permintaan ubah jadwal dari peserta
 */
class PermintaanReschedule extends Model
{
    const STATUS_MENUNGGU = 'menunggu';
    const STATUS_DISETUJUI = 'disetujui';
    const STATUS_DITOLAK = 'ditolak';

    protected $table = 'permintaan_reschedule';

    protected $fillable = [
        'penugasan_jadwal_id',
        'jadwal_tes_baru_id',
        'alasan',
        'status',
        'alasan_penolakan',
        'diproses_oleh',
        'diproses_at',
    ];

    protected $casts = [
        'diproses_at' => 'datetime',
    ];

    public function penugasanJadwal(): BelongsTo
    {
        return $this->belongsTo(PenugasanJadwal::class, 'penugasan_jadwal_id');
    }

    public function jadwalTesBaru(): BelongsTo
    {
        return $this->belongsTo(JadwalTes::class, 'jadwal_tes_baru_id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
