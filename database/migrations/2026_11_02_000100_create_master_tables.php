<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama');
            $table->string('nomor_identitas')->nullable(); // boleh kosong, diisi admin nanti
            $table->string('jenis_identitas', 10)->nullable(); // NIDN atau NUPTK
            $table->string('bidang_keahlian')->nullable();
            $table->boolean('boleh_pembimbing_1')->default(true);
            $table->boolean('boleh_pembimbing_2')->default(true);
            $table->boolean('boleh_penguji')->default(true);
            $table->unsignedSmallInteger('kuota_pembimbing_1')->default(0);
            $table->unsignedSmallInteger('kuota_pembimbing_2')->default(0);
            $table->unsignedSmallInteger('kuota_penguji_1')->default(0);
            $table->unsignedSmallInteger('kuota_penguji_2')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nim')->unique();
            $table->string('nama');
            $table->string('angkatan', 4)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('ruang', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->timestamps();
        });

        Schema::create('periode', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // contoh: 2026/2027 Ganjil
            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });

        Schema::create('tahap', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique(); // judul, sempro, sidang
            $table->string('nama');
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();
        });

        Schema::create('syarat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahap_id')->constrained('tahap')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jenis', 20); // angka, link, centang
            $table->decimal('nilai_minimal', 8, 2)->nullable();
            $table->string('keterangan')->nullable();
            $table->boolean('wajib')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syarat');
        Schema::dropIfExists('tahap');
        Schema::dropIfExists('periode');
        Schema::dropIfExists('ruang');
        Schema::dropIfExists('mahasiswa');
        Schema::dropIfExists('dosen');
    }
};
