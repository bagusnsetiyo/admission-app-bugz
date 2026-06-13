<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckinRequest;
use App\Models\JadwalTes;
use App\Models\KehadiranTes;
use App\Models\PenugasanJadwal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * KehadiranController — check-in operator & laporan kehadiran
 */
class KehadiranController extends Controller
{
    /**
     * POST /api/kehadiran/checkin — check-in via QR token (publik + PIN)
     */
    public function checkin(CheckinRequest $request): JsonResponse
    {
        $expectedPin = env('OPERATOR_PIN', 'pmblapangan2025');
        if ($request->operator_pin !== $expectedPin) {
            return response()->json(['success' => false, 'message' => 'PIN operator tidak valid'], 403);
        }

        $penugasan = PenugasanJadwal::with(['pendaftar', 'jadwalTes', 'kehadiran'])
            ->where('checkin_token', $request->token)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->first();

        if (!$penugasan) {
            return response()->json(['success' => false, 'message' => 'Tiket tidak valid'], 404);
        }

        $jadwal = $penugasan->jadwalTes;
        $today = Carbon::today('Asia/Jakarta');
        $jadwalDate = Carbon::parse($jadwal->tanggal_mulai)->timezone('Asia/Jakarta')->startOfDay();

        if (!$jadwalDate->equalTo($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak valid untuk sesi hari ini',
            ], 409);
        }

        if ($penugasan->kehadiran) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta sudah check-in sebelumnya',
            ], 409);
        }

        $kehadiran = KehadiranTes::create([
            'penugasan_jadwal_id'  => $penugasan->id,
            'status'               => KehadiranTes::STATUS_HADIR,
            'checked_in_at'        => now(),
            'operator_keterangan'  => 'QR Scan',
        ]);

        $penugasan->update(['status' => PenugasanJadwal::STATUS_HADIR]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil',
            'data'    => [
                'nama'              => $penugasan->pendaftar->nama,
                'nomor_pendaftaran' => $penugasan->pendaftar->nomor_pendaftaran,
                'prodi'             => $penugasan->pendaftar->prodi,
                'jadwal'            => $jadwal->judul,
                'lokasi'            => $jadwal->lokasi,
                'kehadiran'         => $kehadiran,
            ],
        ]);
    }

    /**
     * GET /api/kehadiran/sesi/{jadwalTesId} — laporan kehadiran per sesi (admin)
     */
    public function sesi(int $jadwalTesId): JsonResponse
    {
        $jadwal = JadwalTes::find($jadwalTesId);
        if (!$jadwal) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }

        $list = PenugasanJadwal::with(['pendaftar', 'kehadiran'])
            ->where('jadwal_tes_id', $jadwalTesId)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->get()
            ->map(fn ($p) => [
                'id'                => $p->id,
                'nama'              => $p->pendaftar->nama,
                'nomor_pendaftaran' => $p->pendaftar->nomor_pendaftaran,
                'prodi'             => $p->pendaftar->prodi,
                'status_penugasan'  => $p->status,
                'kehadiran'         => $p->kehadiran,
            ]);

        $hadir = $list->filter(fn ($p) => $p['kehadiran'] !== null)->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'jadwal'      => $jadwal,
                'peserta'     => $list,
                'total'       => $list->count(),
                'hadir'       => $hadir,
                'tidak_hadir' => $list->count() - $hadir,
            ],
        ]);
    }
}
