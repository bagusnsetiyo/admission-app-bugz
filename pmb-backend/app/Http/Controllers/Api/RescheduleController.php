<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessRescheduleRequest;
use App\Http\Requests\StoreRescheduleRequest;
use App\Models\JadwalTes;
use App\Models\Pendaftar;
use App\Models\PenugasanJadwal;
use App\Models\PermintaanReschedule;
use App\Services\JadwalHubService;
use App\Services\PendaftarVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RescheduleController — kelola permintaan reschedule jadwal
 */
class RescheduleController extends Controller
{
    public function __construct(
        private JadwalHubService $service,
        private PendaftarVerificationService $verification,
    ) {}

    /**
     * POST /api/reschedule — ajukan reschedule (publik)
     */
    public function store(StoreRescheduleRequest $request): JsonResponse
    {
        $pendaftar = $this->verification->findVerified(
            $request->nomor_pendaftaran,
            $request->verifikasi_hp
        );

        if (!$pendaftar) {
            return response()->json([
                'success' => false,
                'message' => PendaftarVerificationService::GENERIC_FAIL_MESSAGE,
            ], 404);
        }

        $penugasan = PenugasanJadwal::with('jadwalTes')->find($request->penugasan_jadwal_id);
        if (!$penugasan || $penugasan->pendaftar_id !== $pendaftar->id) {
            return response()->json(['success' => false, 'message' => 'Penugasan tidak ditemukan'], 404);
        }

        if ($penugasan->status === PenugasanJadwal::STATUS_BATAL) {
            return response()->json(['success' => false, 'message' => 'Penugasan sudah dibatalkan'], 422);
        }

        $pending = PermintaanReschedule::where('penugasan_jadwal_id', $penugasan->id)
            ->where('status', PermintaanReschedule::STATUS_MENUNGGU)
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki permintaan reschedule yang menunggu persetujuan',
            ], 422);
        }

        $jadwalBaru = JadwalTes::find($request->jadwal_tes_baru_id);
        if (!$jadwalBaru || $jadwalBaru->status !== JadwalTes::STATUS_AKTIF) {
            return response()->json(['success' => false, 'message' => 'Slot tujuan tidak tersedia'], 422);
        }

        if ($jadwalBaru->jenis !== $penugasan->jadwalTes->jenis) {
            return response()->json(['success' => false, 'message' => 'Jenis jadwal tujuan harus sama'], 422);
        }

        if ($error = $this->service->checkKapasitas($jadwalBaru)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        if ($error = $this->service->checkConflict($pendaftar, $jadwalBaru, $penugasan->id)) {
            return response()->json(['success' => false, 'message' => $error], 409);
        }

        $permintaan = PermintaanReschedule::create([
            'penugasan_jadwal_id' => $penugasan->id,
            'jadwal_tes_baru_id'  => $jadwalBaru->id,
            'alasan'              => $request->alasan,
            'status'              => PermintaanReschedule::STATUS_MENUNGGU,
        ]);

        $this->service->logNotifikasi(
            $pendaftar->id,
            'reschedule_diajukan',
            "{$pendaftar->nama} mengajukan reschedule jadwal {$penugasan->jadwalTes->judul}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Permintaan reschedule berhasil diajukan',
            'data'    => $permintaan,
        ], 201);
    }

    /**
     * GET /api/reschedule — list permintaan (admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = PermintaanReschedule::with([
            'penugasanJadwal.pendaftar',
            'penugasanJadwal.jadwalTes',
            'jadwalTesBaru',
        ])->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /**
     * PATCH /api/reschedule/{id} — approve/reject (admin)
     */
    public function process(ProcessRescheduleRequest $request, int $id): JsonResponse
    {
        $permintaan = PermintaanReschedule::with(['penugasanJadwal.pendaftar', 'penugasanJadwal.jadwalTes', 'jadwalTesBaru'])
            ->find($id);

        if (!$permintaan) {
            return response()->json(['success' => false, 'message' => 'Permintaan tidak ditemukan'], 404);
        }

        if ($permintaan->status !== PermintaanReschedule::STATUS_MENUNGGU) {
            return response()->json(['success' => false, 'message' => 'Permintaan sudah diproses'], 422);
        }

        if ($request->action === 'reject') {
            $permintaan->update([
                'status'             => PermintaanReschedule::STATUS_DITOLAK,
                'alasan_penolakan'   => $request->alasan_penolakan,
                'diproses_oleh'      => $request->user()->id,
                'diproses_at'        => now(),
            ]);

            $this->service->logNotifikasi(
                $permintaan->penugasanJadwal->pendaftar_id,
                'reschedule_ditolak',
                "Permintaan reschedule ditolak: {$request->alasan_penolakan}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Permintaan reschedule ditolak',
                'data'    => $permintaan->fresh(),
            ]);
        }

        $jadwalBaru = $permintaan->jadwalTesBaru;
        $pendaftar = $permintaan->penugasanJadwal->pendaftar;

        if ($error = $this->service->checkKapasitas($jadwalBaru)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        if ($error = $this->service->checkConflict($pendaftar, $jadwalBaru, $permintaan->penugasan_jadwal_id)) {
            return response()->json(['success' => false, 'message' => $error], 409);
        }

        try {
            DB::transaction(function () use ($permintaan, $jadwalBaru, $pendaftar, $request) {
                $permintaan->penugasanJadwal->update(['jadwal_tes_id' => $jadwalBaru->id]);

                $permintaan->update([
                    'status'        => PermintaanReschedule::STATUS_DISETUJUI,
                    'diproses_oleh' => $request->user()->id,
                    'diproses_at'   => now(),
                ]);

                $this->service->logNotifikasi(
                    $pendaftar->id,
                    'reschedule_disetujui',
                    "Jadwal Anda diubah ke {$jadwalBaru->judul}"
                );
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memproses reschedule'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permintaan reschedule disetujui',
            'data'    => $permintaan->fresh()->load(['penugasanJadwal.jadwalTes', 'jadwalTesBaru']),
        ]);
    }
}
