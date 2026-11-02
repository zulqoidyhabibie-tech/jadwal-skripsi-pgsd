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
    });

    // Admin saja
    Route::middleware('peran:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('dosen', Admin\DosenController::class)->except('show')->parameters(['dosen' => 'dosen']);
        Route::post('dosen-impor', [Admin\DosenController::class, 'import'])->name('dosen.import');
        Route::resource('mahasiswa', Admin\MahasiswaController::class)->except(['show', 'destroy'])->parameters(['mahasiswa' => 'mahasiswa']);
        Route::post('mahasiswa-impor', [Admin\MahasiswaController::class, 'import'])->name('mahasiswa.import');
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
