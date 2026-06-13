<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model JadwalTes — slot jadwal tes seleksi atau wawancara
 */
class JadwalTes extends Model
{
    const JENIS_TES_SELEKSI = 'tes_seleksi';
    const JENIS_WAWANCARA = 'wawancara';

    const STATUS_AKTIF = 'aktif';
    const STATUS_NONAKTIF = 'nonaktif';
    const STATUS_SELESAI = 'selesai';

    protected $table = 'jadwal_tes';

    protected $fillable = [
        'jenis',
        'judul',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'kapasitas',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'datetime',
        'tanggal_selesai' => 'datetime',
        'kapasitas'       => 'integer',
    ];

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanJadwal::class, 'jadwal_tes_id');
    }

    public function penugasanAktif(): HasMany
    {
        return $this->hasMany(PenugasanJadwal::class, 'jadwal_tes_id')
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
