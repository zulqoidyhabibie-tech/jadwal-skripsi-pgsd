<?php

namespace Database\Seeders;

use App\Models\Periode;
use App\Models\Syarat;
use App\Models\Tahap;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $judul = Tahap::firstOrCreate(['kode' => 'judul'], ['nama' => 'Pengajuan judul', 'urutan' => 1]);
        $sempro = Tahap::firstOrCreate(['kode' => 'sempro'], ['nama' => 'Seminar proposal', 'urutan' => 2]);
        $sidang = Tahap::firstOrCreate(['kode' => 'sidang'], ['nama' => 'Sidang skripsi', 'urutan' => 3]);

        Periode::firstOrCreate(['nama' => '2026/2027 Ganjil'], ['aktif' => true]);

        // Contoh syarat awal. Admin bisa mengubah atau menghapusnya di menu Atur syarat.
        if (! Syarat::exists()) {
            $contoh = [
                [$judul, 'SKS lulus', 'angka', 100, 'Lihat transkrip terbaru'],
                [$judul, 'Transkrip nilai', 'link', null, 'Link Google Drive yang bisa dibuka'],
                [$sempro, 'SKS lulus', 'angka', 110, null],
                [$sempro, 'IPK', 'angka', 2.75, null],
                [$sempro, 'Draf proposal (Bab 1-3)', 'link', null, 'Link Google Drive yang bisa dibuka'],
                [$sempro, 'Bukti pembayaran seminar', 'link', null, null],
                [$sempro, 'Sudah bimbingan minimal 8 kali', 'centang', null, null],
                [$sidang, 'SKS lulus', 'angka', 140, null],
                [$sidang, 'Naskah skripsi lengkap', 'link', null, 'Link Google Drive yang bisa dibuka'],
                [$sidang, 'Surat bebas pustaka', 'link', null, null],
                [$sidang, 'Bukti pembayaran sidang', 'link', null, null],
            ];
            foreach ($contoh as $i => [$t, $nama, $jenis, $min, $ket]) {
                Syarat::create(['tahap_id' => $t->id, 'nama' => $nama, 'jenis' => $jenis, 'nilai_minimal' => $min, 'keterangan' => $ket, 'wajib' => true, 'urutan' => $i]);
            }
        }

        User::firstOrCreate(['username' => 'admin'], [
            'name' => 'Admin Prodi PGSD',
            'email' => env('ADMIN_EMAIL', 'admin@pgsd.local'),
            'password' => env('ADMIN_PASSWORD', 'gantipassword123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
