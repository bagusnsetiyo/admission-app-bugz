<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalTesRequest;
use App\Http\Requests\UpdateJadwalTesRequest;
use App\Http\Requests\VerifyJadwalRequest;
use App\Models\JadwalTes;
use App\Models\Pendaftar;
use App\Models\PenugasanJadwal;
use App\Services\JadwalHubService;
use App\Services\PendaftarVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JadwalTesController — CRUD slot jadwal tes & wawancara
 */
class JadwalTesController extends Controller
{
    public function __construct(
        private JadwalHubService $service,
        private PendaftarVerificationService $verification,
    ) {}

    /**
     * GET /api/jadwal-tes — daftar semua slot (admin)
     */
    public function index(): JsonResponse
    {
        $list = JadwalTes::orderBy('tanggal_mulai')
            ->get()
            ->map(fn ($j) => $this->formatJadwal($j));

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * GET /api/jadwal-tes/tersedia — slot aktif yang masih ada kuota (publik)
     */
    public function tersedia(Request $request): JsonResponse
    {
        $jenis = $request->query('jenis', JadwalTes::JENIS_TES_SELEKSI);

        $list = JadwalTes::where('status', JadwalTes::STATUS_AKTIF)
            ->where('jenis', $jenis)
            ->where('tanggal_mulai', '>', now())
            ->get()
            ->filter(fn ($j) => $this->service->sisaKuota($j) > 0)
            ->map(fn ($j) => $this->formatJadwal($j))
            ->values();

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * POST /api/jadwal-tes — buat slot baru (admin)
     */
    public function store(StoreJadwalTesRequest $request): JsonResponse
    {
        try {
            $jadwal = JadwalTes::create([
                ...$request->validated(),
                'status'     => JadwalTes::STATUS_AKTIF,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dibuat',
                'data'    => $this->formatJadwal($jadwal),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat jadwal'], 500);
        }
    }

    /**
     * PATCH /api/jadwal-tes/{id} — update slot (admin)
     */
    public function update(UpdateJadwalTesRequest $request, int $id): JsonResponse
    {
        try {
            $jadwal = JadwalTes::findOrFail($id);
            $jadwal->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diperbarui',
                'data'    => $this->formatJadwal($jadwal->fresh()),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }
    }

    /**
     * DELETE /api/jadwal-tes/{id} — nonaktifkan slot (admin)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $jadwal = JadwalTes::findOrFail($id);
            $jadwal->update(['status' => JadwalTes::STATUS_NONAKTIF]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dinonaktifkan',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }
    }

    /**
     * GET /api/jadwal-tes/{id}/peserta — daftar peserta per slot (admin)
     */
    public function peserta(int $id): JsonResponse
    {
        $jadwal = JadwalTes::find($id);
        if (!$jadwal) {
            return response()->json(['success' => false, 'message' => 'Jadwal tidak ditemukan'], 404);
        }

        $list = PenugasanJadwal::with(['pendaftar', 'kehadiran'])
            ->where('jadwal_tes_id', $id)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->get()
            ->map(fn ($p) => [
                'id'                => $p->id,
                'checkin_token'     => $p->checkin_token,
                'status'            => $p->status,
                'pendaftar'         => [
                    'id'                => $p->pendaftar->id,
                    'nama'              => $p->pendaftar->nama,
                    'nomor_pendaftaran' => $p->pendaftar->nomor_pendaftaran,
                    'prodi'             => $p->pendaftar->prodi,
                ],
                'kehadiran'         => $p->kehadiran,
            ]);

        return response()->json(['success' => true, 'data' => $list]);
    }

    /**
     * POST /api/pendaftar/{nomor}/jadwal — jadwal milik peserta (wajib verifikasi HP)
     */
    public function byNomorPendaftar(VerifyJadwalRequest $request, string $nomorPendaftaran): JsonResponse
    {
        $pendaftar = $this->verification->findVerified($nomorPendaftaran, $request->verifikasi_hp);

        if (!$pendaftar) {
            return response()->json([
                'success' => false,
                'message' => PendaftarVerificationService::GENERIC_FAIL_MESSAGE,
            ], 404);
        }

        $list = PenugasanJadwal::with(['jadwalTes', 'permintaanReschedule' => fn ($q) => $q->latest()])
            ->where('pendaftar_id', $pendaftar->id)
            ->where('status', '!=', PenugasanJadwal::STATUS_BATAL)
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'checkin_token'   => $p->checkin_token,
                'status'          => $p->status,
                'jadwal'          => $this->formatJadwal($p->jadwalTes),
                'reschedule'      => $p->permintaanReschedule->first(),
            ]);

        return response()->json(['success' => true, 'data' => $list]);
    }

    private function formatJadwal(JadwalTes $jadwal): array
    {
        $terisi = $jadwal->penugasanAktif()->count();

        return [
            'id'              => $jadwal->id,
            'jenis'           => $jadwal->jenis,
            'judul'           => $jadwal->judul,
            'tanggal_mulai'   => $jadwal->tanggal_mulai,
            'tanggal_selesai' => $jadwal->tanggal_selesai,
            'lokasi'          => $jadwal->lokasi,
            'kapasitas'       => $jadwal->kapasitas,
            'terisi'          => $terisi,
            'sisa_kuota'      => max(0, $jadwal->kapasitas - $terisi),
            'status'          => $jadwal->status,
            'catatan'         => $jadwal->catatan,
        ];
    }
}
