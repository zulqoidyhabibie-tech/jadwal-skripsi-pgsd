<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImporDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_impor_tab_data_mhs_dari_sheet_lama_beserta_pembimbing(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('username', 'admin')->first();
        Dosen::create(['nama' => 'Dr. Zulqoidi R. Habibie, M.Pd']);
        Dosen::create(['nama' => 'Megawati, M.Pd']);

        // Susunan kolom sama dengan ekspor CSV tab DATA MHS di Google Sheet.
        $csv = "\"Nama Lengkap\",\"NPM\",\"Angkatan\",\"Pembimbing 1\",\"Pembimbing 2\",\"JUDUL SKRIPSI\",\"\",\"Judul Skripsi Final\",\"\",\"\"\n"
            ."\"Putri Ananda\",\"2021130045\",\"2021\",\"Zulqoidi R. Habibie\",\"megawati, M.Pd\",\"Judul awal\",\"\",\"Judul final PBL kelas IV\",\"\",\"\"\n"
            ."\"Rizki Pratama\",\"2021130012\",\"2021\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"\n"
            ."\"Dewi Lestari\",\"2021130027\",\"2021\",\"Pak Salah Ketik\",\"Megawati, M.Pd\",\"Judul\",\"\",\"\",\"\",\"\"\n";
        $file = UploadedFile::fake()->createWithContent('data-mhs.csv', $csv);

        $this->actingAs($admin)->post(route('admin.mahasiswa.import'), ['file' => $file])
            ->assertSessionHas('err', fn ($pesan) => str_contains($pesan, '3 data mahasiswa') && str_contains($pesan, 'Pak Salah Ketik'));

        $putri = Mahasiswa::where('nim', '2021130045')->first();
        $this->assertSame('Judul final PBL kelas IV', $putri->skripsi->judul);
        $this->assertSame('disetujui', $putri->skripsi->status);
        $this->assertSame('Megawati, M.Pd', $putri->skripsi->pembimbing2->nama);
        $this->assertSame('lulus', $putri->pendaftaran()->first()->status);

        $this->assertNull(Mahasiswa::where('nim', '2021130012')->first()->skripsi);
        $this->assertNull(Mahasiswa::where('nim', '2021130027')->first()->skripsi);
    }

    public function test_file_dosen_dari_sheet_bisa_diimpor(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('username', 'admin')->first();
        $csv = "\xEF\xBB\xBFnama;jenis_identitas;nomor_identitas;boleh_p1;boleh_p2;boleh_penguji;kuota_p1;kuota_p2;kuota_u1;kuota_u2\n"
            ."Dr. Zulqoidi R. Habibie, M.Pd;NIDN;1030059002;1;1;1;7;7;4;4\n"
            ."Muhammad Dwi Kurniadi, M.Pd;;;0;0;1;0;0;9;9\n";
        $this->actingAs($admin)->post(route('admin.dosen.import'), ['file' => UploadedFile::fake()->createWithContent('d.csv', $csv)])
            ->assertSessionHas('ok');

        $z = Dosen::where('nama', 'Dr. Zulqoidi R. Habibie, M.Pd')->first();
        $this->assertSame(7, $z->kuota_pembimbing_1);
        $this->assertSame(4, $z->kuota_penguji_2);
        $this->assertFalse(Dosen::where('nama', 'like', 'Muhammad Dwi%')->first()->boleh_pembimbing_1);
    }
}
