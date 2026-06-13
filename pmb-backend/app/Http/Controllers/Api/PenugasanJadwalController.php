<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AutoBatchPenugasanRequest;
use App\Http\Requests\StorePenugasanRequest;
use App\Models\JadwalTes;
use App\Models\Pendaftar;
use App\Models\PenugasanJadwal;
use App\Services\JadwalHubService;
use Illuminate\Http\JsonResponse;

/**
 * PenugasanJadwalController — assign & batalkan penugasan peserta
 */
class PenugasanJadwalController extends Controller
{
    public function __construct(private JadwalHubService $service) {}

    /**
     * POST /api/penugasan — assign manual (admin)
     */
    public function store(StorePenugasanRequest $request): JsonResponse
    {
        try {
            $jadwal = JadwalTes::findOrFail($request->jadwal_tes_id);
            $pendaftar = Pendaftar::findOrFail($request->pendaftar_id);

            $penugasan = $this->service->assignPendaftar($jadwal, $pendaftar, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Peserta berhasil ditugaskan',
                'data'    => $penugasan->load(['pendaftar', 'jadwalTes']),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
    }

    /**
     * POST /api/penugasan/auto-batch — auto assign per prodi (admin)
     */
    public function autoBatch(AutoBatchPenugasanRequest $request): JsonResponse
    {
        try {
            $jadwal = JadwalTes::findOrFail($request->jadwal_tes_id);

            if ($jadwal->jenis !== $request->jenis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis jadwal tidak sesuai dengan slot',
                ], 422);
            }

            $result = $this->service->autoBatch($jadwal, $request->prodi, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => count($result['berhasil']) . ' peserta berhasil ditugaskan',
                'data'    => [
                    'berhasil' => count($result['berhasil']),
                    'gagal'    => $result['gagal'],
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }
    }

    /**
     * DELETE /api/penugasan/{id} — batalkan penugasan (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $penugasan = PenugasanJadwal::findOrFail($id);
            $penugasan->update(['status' => PenugasanJadwal::STATUS_BATAL]);

            return response()->json([
                'success' => true,
                'message' => 'Penugasan berhasil dibatalkan',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Penugasan tidak ditemukan'], 404);
        }
    }
}
