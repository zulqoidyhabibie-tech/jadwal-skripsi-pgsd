<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_ujian', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30);
            $table->foreignId('tahap_id')->constrained('tahap');
            $table->foreignId('periode_id')->nullable()->constrained('periode')->nullOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->foreignId('ruang_id')->constrained('ruang');
            $table->unsignedTinyInteger('kapasitas')->default(3); // jumlah mahasiswa per sesi, seperti di Sheet
            $table->boolean('terbit')->default(false); // terlihat oleh mahasiswa dan dosen
            $table->timestamps();
            $table->unique(['kode', 'periode_id']);
        });

        Schema::create('jadwal_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_ujian_id')->constrained('sesi_ujian')->cascadeOnDelete();
            $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftaran')->cascadeOnDelete();
            $table->foreignId('penguji_1_id')->constrained('dosen');
            $table->foreignId('penguji_2_id')->constrained('dosen');
            $table->string('hasil', 20)->nullable(); // lulus, lulus_revisi, mengulang
            $table->string('catatan_hasil', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_ujian');
        Schema::dropIfExists('sesi_ujian');
    }
};
