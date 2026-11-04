<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DosenPortalController;
use App\Http\Controllers\Kelola;
use App\Http\Controllers\MahasiswaPortalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/beranda');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formMasuk'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'formDaftar'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');
    Route::get('/beranda', BerandaController::class)->name('beranda');
    Route::get('/password', [AuthController::class, 'formPassword'])->name('password');
    Route::put('/password', [AuthController::class, 'gantiPassword'])->middleware('throttle:6,1');

    // Mahasiswa
    Route::middleware('peran:mahasiswa')->prefix('mahasiswa')->name('mhs.')->group(function () {
        Route::get('/', [MahasiswaPortalController::class, 'beranda'])->name('beranda');
        Route::get('/tahap/{kode}', [MahasiswaPortalController::class, 'tahap'])->name('tahap');
        Route::post('/tahap/{kode}', [MahasiswaPortalController::class, 'simpan'])->name('simpan');
    });

    // Dosen
    Route::middleware('peran:dosen,kaprodi')->prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/', [DosenPortalController::class, 'beranda'])->name('beranda');
    });

    // Kaprodi dan admin
    Route::middleware('peran:kaprodi')->prefix('kelola')->name('kelola.')->group(function () {
        Route::get('/judul', [Kelola\JudulController::class, 'index'])->name('judul.index');
        Route::post('/judul/{pendaftaran}/setujui', [Kelola\JudulController::class, 'setujui'])->name('judul.setujui');
        Route::post('/judul/{pendaftaran}/revisi', [Kelola\JudulController::class, 'revisi'])->name('judul.revisi');
        Route::get('/verifikasi', [Kelola\VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('/verifikasi/{pendaftaran}', [Kelola\VerifikasiController::class, 'show'])->name('verifikasi.show');
        Route::post('/verifikasi/{pendaftaran}', [Kelola\VerifikasiController::class, 'putuskan'])->name('verifikasi.putuskan');
        Route::get('/rekap-dosen', Kelola\RekapDosenController::class)->name('rekap');
        Route::get('/jadwal', [Kelola\JadwalController::class, 'index'])->name('jadwal.index');
        Route::get('/jadwal/ekspor', [Kelola\JadwalController::class, 'ekspor'])->name('jadwal.ekspor');
        Route::get('/jadwal/sesi/{sesi}', [Kelola\JadwalController::class, 'sesi'])->name('jadwal.sesi');
        Route::post('/jadwal/slot/{jadwal}/hasil', [Kelola\JadwalController::class, 'hasil'])->name('jadwal.hasil');
    });

    // Kartu ujian: kaprodi/admin, atau mahasiswa pemiliknya
    Route::get('/kartu-ujian/{jadwal}', [Kelola\JadwalController::class, 'kartu'])->name('jadwal.kartu');

    // Menyusun jadwal: admin
    Route::middleware('peran:admin')->prefix('kelola/jadwal')->name('kelola.jadwal.')->group(function () {
        Route::post('/sesi', [Kelola\JadwalController::class, 'buatSesi'])->name('sesi.store');
        Route::put('/sesi/{sesi}', [Kelola\JadwalController::class, 'ubahSesi'])->name('sesi.update');
        Route::delete('/sesi/{sesi}', [Kelola\JadwalController::class, 'hapusSesi'])->name('sesi.destroy');
        Route::post('/sesi/{sesi}/terbitkan', [Kelola\JadwalController::class, 'terbitkan'])->name('sesi.terbitkan');
        Route::post('/sesi/{sesi}/slot', [Kelola\JadwalController::class, 'tambahSlot'])->name('slot.store');
        Route::delete('/slot/{jadwal}', [Kelola\JadwalController::class, 'hapusSlot'])->name('slot.destroy');
    });

    // Admin saja
    Route::middleware('peran:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('dosen', Admin\DosenController::class)->except('show')->parameters(['dosen' => 'dosen']);
        Route::post('dosen-impor', [Admin\DosenController::class, 'import'])->name('dosen.import');
        Route::get('dosen-template', [Admin\DosenController::class, 'template'])->name('dosen.template');
        Route::post('dosen/{dosen}/kaprodi', [Admin\DosenController::class, 'jadikanKaprodi'])->name('dosen.kaprodi');
        Route::delete('dosen/{dosen}/kaprodi', [Admin\DosenController::class, 'lepasKaprodi'])->name('dosen.lepas-kaprodi');
        Route::resource('mahasiswa', Admin\MahasiswaController::class)->except(['show', 'destroy'])->parameters(['mahasiswa' => 'mahasiswa']);
        Route::post('mahasiswa-impor', [Admin\MahasiswaController::class, 'import'])->name('mahasiswa.import');
        Route::get('mahasiswa-template', [Admin\MahasiswaController::class, 'template'])->name('mahasiswa.template');
        Route::post('ruang-impor', [Admin\RuangController::class, 'import'])->name('ruang.import');
        Route::get('ruang-template', [Admin\RuangController::class, 'template'])->name('ruang.template');
        Route::resource('ruang', Admin\RuangController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['ruang' => 'ruang']);
        Route::resource('syarat', Admin\SyaratController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['syarat' => 'syarat']);
        Route::get('pengguna', [Admin\PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('pengguna', [Admin\PenggunaController::class, 'store'])->name('pengguna.store');
        Route::post('pengguna/{user}/aktifkan', [Admin\PenggunaController::class, 'aktifkan'])->name('pengguna.aktifkan');
        Route::post('pengguna/{user}/reset', [Admin\PenggunaController::class, 'resetPassword'])->name('pengguna.reset');
        Route::get('periode', [Admin\PeriodeController::class, 'index'])->name('periode.index');
        Route::post('periode', [Admin\PeriodeController::class, 'store'])->name('periode.store');
        Route::post('periode/{periode}/aktifkan', [Admin\PeriodeController::class, 'aktifkan'])->name('periode.aktifkan');
    });
});
