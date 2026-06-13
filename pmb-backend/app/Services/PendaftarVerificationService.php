<?php

namespace App\Services;

use App\Models\Pendaftar;

/**
 * PendaftarVerificationService — verifikasi kepemilikan data via nomor HP
 */
class PendaftarVerificationService
{
    public const GENERIC_FAIL_MESSAGE = 'Nomor pendaftaran atau verifikasi tidak valid';

    /**
     * Cari pendaftar dan validasi kepemilikan via nomor HP (full atau 4 digit terakhir)
     */
    public function findVerified(string $nomorPendaftaran, string $verifikasiHp): ?Pendaftar
    {
        $pendaftar = Pendaftar::where('nomor_pendaftaran', $nomorPendaftaran)->first();

        if (!$pendaftar || !$this->verifyHp($pendaftar, $verifikasiHp)) {
            return null;
        }

        return $pendaftar;
    }

    public function verifyHp(Pendaftar $pendaftar, string $input): bool
    {
        $normalized = preg_replace('/\D/', '', $input) ?? '';
        $stored = preg_replace('/\D/', '', $pendaftar->nomor_hp) ?? '';

        if ($normalized === '' || $stored === '') {
            return false;
        }

        return $normalized === $stored
            || (strlen($normalized) === 4 && str_ends_with($stored, $normalized));
    }

    /**
     * Mask nomor HP untuk tampilan publik setelah verifikasi
     */
    public function maskHp(string $nomorHp): string
    {
        $digits = preg_replace('/\D/', '', $nomorHp) ?? '';

        if (strlen($digits) < 4) {
            return '****';
        }

        return substr($digits, 0, 4) . str_repeat('*', max(0, strlen($digits) - 8)) . substr($digits, -4);
    }

    /**
     * Data aman yang boleh ditampilkan setelah verifikasi
     */
    public function toPublicArray(Pendaftar $pendaftar): array
    {
        return [
            'id'                => $pendaftar->id,
            'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
            'nama'              => $pendaftar->nama,
            'nomor_hp_masked'   => $this->maskHp($pendaftar->nomor_hp),
            'prodi'             => $pendaftar->prodi,
            'jalur'             => $pendaftar->jalur,
            'status'            => $pendaftar->status,
            'heregistrasi_at'   => $pendaftar->heregistrasi_at,
            'created_at'        => $pendaftar->created_at,
        ];
    }
}
