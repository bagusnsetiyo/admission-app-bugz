<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration modul JadwalHub — tabel penjadwalan tes & wawancara PMB
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_tes', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20);
            $table->string('judul', 100);
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai');
            $table->string('lokasi', 150);
            $table->unsignedSmallInteger('kapasitas')->default(30);
            $table->string('status', 20)->default('aktif');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal_mulai');
            $table->index(['jenis', 'status']);
        });

        Schema::create('penugasan_jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_tes_id')->constrained('jadwal_tes')->restrictOnDelete();
            $table->foreignId('pendaftar_id')->constrained('pendaftars')->restrictOnDelete();
            $table->char('checkin_token', 36)->unique();
            $table->string('status', 20)->default('terjadwal');
            $table->foreignId('ditugaskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditugaskan_at');
            $table->timestamps();

            $table->unique(['pendaftar_id', 'jadwal_tes_id']);
            $table->index('pendaftar_id');
            $table->index('jadwal_tes_id');
        });

        Schema::create('permintaan_reschedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_jadwal_id')->constrained('penugasan_jadwal')->restrictOnDelete();
            $table->foreignId('jadwal_tes_baru_id')->constrained('jadwal_tes')->restrictOnDelete();
            $table->text('alasan');
            $table->string('status', 20)->default('menunggu');
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('kehadiran_tes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_jadwal_id')->unique()->constrained('penugasan_jadwal')->restrictOnDelete();
            $table->string('status', 20);
            $table->timestamp('checked_in_at')->nullable();
            $table->string('operator_keterangan', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('notifikasi_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')->nullable()->constrained('pendaftars')->nullOnDelete();
            $table->string('jenis', 30);
            $table->text('pesan');
            $table->boolean('dibaca')->default(false);
            $table->timestamps();

            $table->index(['pendaftar_id', 'dibaca']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_log');
        Schema::dropIfExists('kehadiran_tes');
        Schema::dropIfExists('permintaan_reschedule');
        Schema::dropIfExists('penugasan_jadwal');
        Schema::dropIfExists('jadwal_tes');
    }
};
