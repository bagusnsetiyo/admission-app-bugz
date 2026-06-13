<?php

namespace Database\Seeders;

use App\Models\JadwalTes;
use App\Models\Pendaftar;
use App\Models\PenugasanJadwal;
use App\Models\User;
use App\Services\JadwalHubService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * JadwalHubSeeder — data dummy slot jadwal & penugasan untuk demo
 */
class JadwalHubSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $service = new JadwalHubService();

        $tesSlot = JadwalTes::create([
            'jenis'           => JadwalTes::JENIS_TES_SELEKSI,
            'judul'           => 'Tes Seleksi TI Gelombang 1',
            'tanggal_mulai'   => Carbon::now()->addDays(3)->setTime(8, 0),
            'tanggal_selesai' => Carbon::now()->addDays(3)->setTime(12, 0),
            'lokasi'          => 'Gedung A Lantai 2 — Lab Komputer',
            'kapasitas'       => 30,
            'status'          => JadwalTes::STATUS_AKTIF,
            'catatan'         => 'Bawa alat tulis dan kartu identitas.',
            'created_by'      => $admin?->id,
        ]);

        $wawancaraSlot = JadwalTes::create([
            'jenis'           => JadwalTes::JENIS_WAWANCARA,
            'judul'           => 'Wawancara PMB Gelombang 1',
            'tanggal_mulai'   => Carbon::now()->addDays(7)->setTime(9, 0),
            'tanggal_selesai' => Carbon::now()->addDays(7)->setTime(17, 0),
            'lokasi'          => 'Ruang Wawancara — Gedung B',
            'kapasitas'       => 20,
            'status'          => JadwalTes::STATUS_AKTIF,
            'catatan'         => 'Datang 15 menit sebelum jadwal.',
            'created_by'      => $admin?->id,
        ]);

        $andi = Pendaftar::where('nomor_pendaftaran', 'PMB-2025-1001')->first();
        $siti = Pendaftar::where('nomor_pendaftaran', 'PMB-2025-1002')->first();

        if ($andi) {
            try {
                $service->assignPendaftar($tesSlot, $andi, $admin?->id);
            } catch (\InvalidArgumentException) {
                // Abaikan jika sudah ada
            }
        }

        if ($siti) {
            try {
                $service->assignPendaftar($wawancaraSlot, $siti, $admin?->id);
            } catch (\InvalidArgumentException) {
                // Abaikan jika sudah ada
            }
        }
    }
}
