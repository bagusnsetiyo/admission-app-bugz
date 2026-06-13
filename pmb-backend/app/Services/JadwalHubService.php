<?php

namespace App\Services;

use App\Models\JadwalTes;
use App\Models\NotifikasiLog;
use App\Models\Pendaftar;
use App\Models\PenugasanJadwal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * JadwalHubService — business rules penjadwalan tes & wawancara PMB
 */
class JadwalHubService
{
    /**
     * Validasi eligibility pendaftar untuk jenis jadwal tertentu
     */
    public function checkEligibility(Pendaftar $pendaftar, string $jenis): ?string
    {
        if ($jenis === JadwalTes::JENIS_TES_SELEKSI) {
            if ($pendaftar->status !== Pendaftar::STATUS_MENUNGGU) {
                return 'Tes seleksi hanya untuk pendaftar berstatus Menunggu';
            }
            return null;
        }

        if ($jenis === JadwalTes::JENIS_WAWANCARA) {
            if ($pendaftar->status !== Pendaftar::STATUS_LOLOS) {
                return 'Wawancara hanya untuk pendaftar berstatus Lolos Seleksi';
            }
            if ($pendaftar->heregistrasi_at) {
                return 'Pendaftar sudah heregistrasi, tidak perlu jadwal wawancara';
            }
            return null;
        }

        return 'Jenis jadwal tidak valid';
    }

    /**
     * Cek apakah slot masih ada kuota
     */
    public function checkKapasitas(JadwalTes $jadwal): ?string
    {
        $terisi = $jadwal->penugasanAktif()->count();
        if ($terisi >= $jadwal->kapasitas) {
            return "Slot sudah penuh ({$terisi}/{$jadwal->kapasitas})";
        }
        return null;
    }

    /**
     * Cek konflik jadwal overlap untuk pendaftar
     */
    public function checkConflict(Pendaftar $pendaftar, JadwalTes $jadwalBaru, ?int $excludePenugasanId = null): ?string
    {
        $mulai = Carbon::parse($jadwalBaru->tanggal_mulai);
        $selesai = Carbon::parse($jadwalBaru->tanggal_selesai);

        $query = PenugasanJadwal::query()
            ->where('pendaftar_id', $pendaftar->id)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->whereHas('jadwalTes', function ($q) use ($jadwalBaru, $mulai, $selesai) {
                $q->where('jenis', $jadwalBaru->jenis)
                    ->where('tanggal_mulai', '<', $selesai)
                    ->where('tanggal_selesai', '>', $mulai);
            });

        if ($excludePenugasanId) {
            $query->where('id', '!=', $excludePenugasanId);
        }

        if ($query->exists()) {
            return 'Pendaftar sudah memiliki jadwal di rentang waktu tersebut';
        }

        return null;
    }

    /**
     * Assign satu pendaftar ke slot jadwal
     */
    public function assignPendaftar(
        JadwalTes $jadwal,
        Pendaftar $pendaftar,
        ?int $adminId = null
    ): PenugasanJadwal {
        if ($error = $this->checkEligibility($pendaftar, $jadwal->jenis)) {
            throw new \InvalidArgumentException($error);
        }
        if ($error = $this->checkKapasitas($jadwal)) {
            throw new \InvalidArgumentException($error);
        }
        if ($error = $this->checkConflict($pendaftar, $jadwal)) {
            throw new \InvalidArgumentException($error);
        }

        $existing = PenugasanJadwal::where('pendaftar_id', $pendaftar->id)
            ->where('jadwal_tes_id', $jadwal->id)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException('Pendaftar sudah ditugaskan ke slot ini');
        }

        $penugasan = PenugasanJadwal::create([
            'jadwal_tes_id'   => $jadwal->id,
            'pendaftar_id'    => $pendaftar->id,
            'checkin_token'   => (string) Str::uuid(),
            'status'          => PenugasanJadwal::STATUS_TERJADWAL,
            'ditugaskan_oleh' => $adminId,
            'ditugaskan_at'   => now(),
        ]);

        $this->logNotifikasi(
            $pendaftar->id,
            'jadwal_ditetapkan',
            "Jadwal {$jadwal->judul} ditetapkan pada " .
            Carbon::parse($jadwal->tanggal_mulai)->timezone('Asia/Jakarta')->format('d M Y H:i') .
            " di {$jadwal->lokasi}"
        );

        return $penugasan;
    }

    /**
     * Auto-batch assign pendaftar per prodi ke satu slot
     */
    public function autoBatch(JadwalTes $jadwal, string $prodi, ?int $adminId = null): array
    {
        $query = Pendaftar::where('prodi', $prodi);

        if ($jadwal->jenis === JadwalTes::JENIS_TES_SELEKSI) {
            $query->where('status', Pendaftar::STATUS_MENUNGGU);
        } else {
            $query->where('status', Pendaftar::STATUS_LOLOS)->whereNull('heregistrasi_at');
        }

        $sudahAssign = PenugasanJadwal::where('jadwal_tes_id', $jadwal->id)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->pluck('pendaftar_id');

        $pendaftarList = $query->whereNotIn('id', $sudahAssign)->get();

        $berhasil = [];
        $gagal = [];

        foreach ($pendaftarList as $pendaftar) {
            if ($this->checkKapasitas($jadwal)) {
                $gagal[] = ['pendaftar' => $pendaftar->nama, 'alasan' => 'Slot penuh'];
                break;
            }
            try {
                $berhasil[] = $this->assignPendaftar($jadwal, $pendaftar, $adminId);
                $jadwal->refresh();
            } catch (\InvalidArgumentException $e) {
                $gagal[] = ['pendaftar' => $pendaftar->nama, 'alasan' => $e->getMessage()];
            }
        }

        return ['berhasil' => $berhasil, 'gagal' => $gagal];
    }

    /**
     * Tulis log notifikasi in-app
     */
    public function logNotifikasi(?int $pendaftarId, string $jenis, string $pesan): void
    {
        NotifikasiLog::create([
            'pendaftar_id' => $pendaftarId,
            'jenis'        => $jenis,
            'pesan'        => $pesan,
            'dibaca'       => false,
        ]);
    }

    /**
     * Hitung sisa kuota slot
     */
    public function sisaKuota(JadwalTes $jadwal): int
    {
        $terisi = $jadwal->penugasanAktif()->count();
        return max(0, $jadwal->kapasitas - $terisi);
    }
}
