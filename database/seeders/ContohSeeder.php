<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\Ruang;
use App\Models\Skripsi;
use App\Models\Tahap;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Data contoh untuk uji coba lokal. Jangan dijalankan di server produksi. */
class ContohSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = [
            ['Dr. Rina Wulandari, M.Pd.', 8, 6], ['Ahmad Fauzi, M.Pd.', 6, 6], ['Siti Rahmah, M.Pd.', 6, 6],
            ['Dedi Kurniawan, M.Pd.', 7, 6], ['Nur Aini, M.Pd.', 6, 6], ['Hendra Saputra, M.Pd.', 6, 6],
        ];
        foreach ($dosen as [$nama, $k1, $k2]) {
            Dosen::firstOrCreate(['nama' => $nama], ['kuota_pembimbing_1' => $k1, 'kuota_pembimbing_2' => $k2, 'kuota_penguji_1' => 6, 'kuota_penguji_2' => 6]);
        }
        foreach (['Ruang 2.1', 'Ruang 2.2', 'Lab Microteaching'] as $r) {
            Ruang::firstOrCreate(['nama' => $r]);
        }
        foreach ([['2021130045', 'Putri Ananda'], ['2021130012', 'Rizki Pratama'], ['2021130027', 'Dewi Lestari']] as [$nim, $nama]) {
            Mahasiswa::firstOrCreate(['nim' => $nim], ['nama' => $nama, 'angkatan' => '2021']);
        }

        $kaprodi = User::firstOrCreate(['username' => 'kaprodi'], ['name' => 'Kaprodi PGSD', 'email' => 'kaprodi@pgsd.local', 'password' => 'password123', 'role' => 'kaprodi', 'is_active' => true]);
        $mhsUser = User::firstOrCreate(['username' => '2021130045'], ['name' => 'Putri Ananda', 'email' => 'putri@contoh.local', 'password' => 'password123', 'role' => 'mahasiswa', 'is_active' => true]);
        Mahasiswa::where('nim', '2021130045')->update(['user_id' => $mhsUser->id]);
        $dosenUser = User::firstOrCreate(['username' => 'siti'], ['name' => 'Siti Rahmah, M.Pd.', 'email' => 'siti@pgsd.local', 'password' => 'password123', 'role' => 'dosen', 'is_active' => true]);
        Dosen::where('nama', 'Siti Rahmah, M.Pd.')->update(['user_id' => $dosenUser->id]);
    
        // Dua mahasiswa contoh yang judulnya sudah disetujui dan siap dijadwalkan seminar proposal.
        $d = Dosen::orderBy('id')->get();
        foreach ([['2021130012', 0, 1], ['2021130027', 3, 4]] as [$nim, $i1, $i2]) {
            $m = Mahasiswa::where('nim', $nim)->first();
            if (Skripsi::where('mahasiswa_id', $m->id)->exists()) {
                continue;
            }
            Skripsi::create(['mahasiswa_id' => $m->id, 'judul' => $nim === '2021130012'
                ? 'Pengaruh media pop-up book terhadap minat baca siswa kelas II SD'
                : 'Analisis kesulitan belajar pecahan pada siswa kelas V SD',
                'status' => 'disetujui', 'pembimbing_1_id' => $d[$i1]->id, 'pembimbing_2_id' => $d[$i2]->id]);
            Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => Tahap::where('kode', 'judul')->value('id'), 'status' => 'lulus']);
            Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => Tahap::where('kode', 'sempro')->value('id'), 'status' => 'terverifikasi', 'diajukan_pada' => now(), 'diverifikasi_pada' => now()]);
        }
    }
}
