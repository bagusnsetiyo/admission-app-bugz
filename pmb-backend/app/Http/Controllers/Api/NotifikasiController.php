<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotifikasiLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * NotifikasiController — log notifikasi in-app JadwalHub
 */
class NotifikasiController extends Controller
{
    /**
     * GET /api/notifikasi — daftar notifikasi (admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotifikasiLog::with('pendaftar')->orderByDesc('created_at');

        if ($request->query('unread') === 'true') {
            $query->where('dibaca', false);
        }

        $list = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data'    => $list,
            'meta'    => [
                'unread' => NotifikasiLog::where('dibaca', false)->count(),
            ],
        ]);
    }

    /**
     * PATCH /api/notifikasi/{id}/baca — tandai notifikasi dibaca (admin)
     */
    public function markRead(int $id): JsonResponse
    {
        $notif = NotifikasiLog::find($id);
        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Notifikasi tidak ditemukan'], 404);
        }

        $notif->update(['dibaca' => true]);

        return response()->json(['success' => true, 'message' => 'Notifikasi ditandai dibaca']);
    }
}
