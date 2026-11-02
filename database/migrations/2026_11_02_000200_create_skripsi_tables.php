<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skripsi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->string('judul', 500);
            $table->string('status', 20)->default('diajukan'); // diajukan, disetujui, revisi
            $table->text('catatan')->nullable();
            $table->foreignId('pembimbing_1_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->foreignId('pembimbing_2_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('tahap_id')->constrained('tahap');
            $table->foreignId('periode_id')->nullable()->constrained('periode')->nullOnDelete();
            // draf, diajukan, ditolak, terverifikasi, dijadwalkan, lulus, mengulang
            $table->string('status', 20)->default('draf');
            $table->text('catatan')->nullable();
            $table->timestamp('diajukan_pada')->nullable();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('isian_syarat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftaran_id')->constrained('pendaftaran')->cascadeOnDelete();
            $table->foreignId('syarat_id')->constrained('syarat')->cascadeOnDelete();
            $table->string('nilai', 1000)->nullable(); // angka, link, atau "1" untuk centang
            $table->string('status', 20)->default('menunggu'); // menunggu, diterima, ditolak
            $table->string('catatan')->nullable();
            $table->timestamps();
            $table->unique(['pendaftaran_id', 'syarat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isian_syarat');
        Schema::dropIfExists('pendaftaran');
        Schema::dropIfExists('skripsi');
    }
};
