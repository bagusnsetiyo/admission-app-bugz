<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\JadwalTesController;
use App\Http\Controllers\Api\KehadiranController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PenugasanJadwalController;
use App\Http\Controllers\Api\PendaftarController;
use App\Http\Controllers\Api\RescheduleController;
use Illuminate\Support\Facades\Route;

/*
 * API Routes — Sistem PMB
 * Semua route di bawah prefix /api secara otomatis
 */

$nomorRegex = 'PMB-[0-9]{4}-[0-9]{4}';

// --- Auth (rate limited) ---
Route::post('/auth/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1');

// --- Publik dengan verifikasi HP (rate limited) ---
Route::middleware('throttle:20,1')->group(function () use ($nomorRegex) {
    Route::post('/pendaftar', [PendaftarController::class, 'store']);
    Route::post('/pendaftar/cek-status', [PendaftarController::class, 'cekStatus']);

    Route::post('/pendaftar/{nomorPendaftaran}/jadwal', [JadwalTesController::class, 'byNomorPendaftar'])
        ->where('nomorPendaftaran', $nomorRegex);
    Route::post('/pendaftar/{nomorPendaftaran}/heregistrasi', [PendaftarController::class, 'heregistrasi'])
        ->where('nomorPendaftaran', $nomorRegex);

    Route::get('/jadwal-tes/tersedia', [JadwalTesController::class, 'tersedia']);
    Route::post('/reschedule', [RescheduleController::class, 'store']);
});

// --- Check-in operator (rate limited ketat) ---
Route::post('/kehadiran/checkin', [KehadiranController::class, 'checkin'])
    ->middleware('throttle:10,1');

// --- Admin (butuh Sanctum token) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
    Route::get('/pendaftar', [PendaftarController::class, 'index']);
    Route::patch('/pendaftar/{id}/status', [PendaftarController::class, 'updateStatus']);
    Route::get('/statistik', [PendaftarController::class, 'statistik']);
    Route::get('/pendaftar/export/csv', [PendaftarController::class, 'exportCsv']);

    // JadwalHub admin
    Route::get('/jadwal-tes', [JadwalTesController::class, 'index']);
    Route::post('/jadwal-tes', [JadwalTesController::class, 'store']);
    Route::patch('/jadwal-tes/{id}', [JadwalTesController::class, 'update']);
    Route::delete('/jadwal-tes/{id}', [JadwalTesController::class, 'destroy']);
    Route::get('/jadwal-tes/{id}/peserta', [JadwalTesController::class, 'peserta']);

    Route::post('/penugasan', [PenugasanJadwalController::class, 'store']);
    Route::post('/penugasan/auto-batch', [PenugasanJadwalController::class, 'autoBatch']);
    Route::delete('/penugasan/{id}', [PenugasanJadwalController::class, 'destroy']);

    Route::get('/reschedule', [RescheduleController::class, 'index']);
    Route::patch('/reschedule/{id}', [RescheduleController::class, 'process']);

    Route::get('/kehadiran/sesi/{jadwalTesId}', [KehadiranController::class, 'sesi']);

    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::patch('/notifikasi/{id}/baca', [NotifikasiController::class, 'markRead']);
});
