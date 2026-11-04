<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImporExcelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        return User::where('username', 'admin')->first();
    }

    private function xlsx(array $baris): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uji').'.xlsx';
        $w = new Writer;
        $w->openToFile($path);
        foreach ($baris as $b) {
            $w->addRow(Row::fromValues($b));
        }
        $w->close();

        return new UploadedFile($path, 'data.xlsx', null, null, true);
    }

    public function test_template_bisa_diunduh_dan_berisi_judul_kolom(): void
    {
        $admin = $this->admin();
        foreach (['dosen' => 'kuota_u2', 'mahasiswa' => 'judul_skripsi', 'ruang' => 'kapasitas'] as $jenis => $kolom) {
            $res = $this->actingAs($admin)->get("/admin/{$jenis}-template")->assertOk();
            $path = $res->getFile()->getPathname();
            $r = new Reader;
            $r->open($path);
            foreach ($r->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $this->assertContains($kolom, $row->toArray());
                    break;
                }
                break;
            }
            $r->close();
        }
    }

    public function test_impor_excel_dosen_mahasiswa_dan_ruang(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/dosen-impor', ['file' => $this->xlsx([
            ['nama', 'jenis_identitas', 'nomor_identitas', 'boleh_p1', 'boleh_p2', 'boleh_penguji', 'kuota_p1', 'kuota_p2', 'kuota_u1', 'kuota_u2'],
            ['Dr. Ani Lestari, M.Pd.', 'NIDN', 1012345678, 'ya', 'ya', 'ya', 8, 8, 10, 10],
            ['Budi Santoso, M.Pd.', '', '', 'tidak', 'ya', 'ya', 0, 6, 0, 10],
        ])])->assertSessionHas('ok', '2 data dosen diimpor.');
        $this->assertSame('1012345678', Dosen::where('nama', 'Dr. Ani Lestari, M.Pd.')->value('nomor_identitas'));
        $this->assertFalse((bool) Dosen::where('nama', 'Budi Santoso, M.Pd.')->value('boleh_pembimbing_1'));

        // Kolom gaya tab DATA MHS, NIM dan HP sebagai angka (nol depan HP hilang di Excel).
        $this->actingAs($admin)->post('/admin/mahasiswa-impor', ['file' => $this->xlsx([
            [],
            ['Nama Lengkap', 'NPM', 'Angkatan', 'No HP', 'Pembimbing 1', 'Pembimbing 2', 'Judul Skripsi'],
            ['Citra Dewi', 2022130001, 2022, 81234567890, 'Ani Lestari', 'Budi Santoso', 'Media Pembelajaran IPA'],
        ])])->assertSessionHas('ok');
        $m = Mahasiswa::where('nim', '2022130001')->first();
        $this->assertSame('081234567890', $m->no_hp);
        $this->assertSame('disetujui', $m->skripsi->status);

        $this->actingAs($admin)->post('/admin/ruang-impor', ['file' => $this->xlsx([
            ['nama', 'kapasitas'], ['Ruang 2.1', 30], ['Lab Microteaching', ''],
        ])])->assertSessionHas('ok', '2 data ruang diimpor.');
        $this->assertSame(30, Ruang::where('nama', 'Ruang 2.1')->value('kapasitas'));
    }

    public function test_berkas_selain_excel_atau_csv_ditolak(): void
    {
        $this->actingAs($this->admin())->post('/admin/ruang-impor', [
            'file' => UploadedFile::fake()->create('data.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('file');
    }

    public function test_dosen_bisa_dijadikan_kaprodi_dan_kaprodi_lama_kembali_dosen(): void
    {
        $admin = $this->admin();
        $u1 = User::create(['name' => 'Ani', 'username' => 'ani', 'email' => 'ani@x.id', 'role' => 'kaprodi', 'is_active' => true, 'password' => 'rahasia123']);
        $u2 = User::create(['name' => 'Budi', 'username' => 'budi', 'email' => 'budi@x.id', 'role' => 'dosen', 'is_active' => true, 'password' => 'rahasia123']);
        Dosen::create(['nama' => 'Ani', 'user_id' => $u1->id]);
        $budi = Dosen::create(['nama' => 'Budi', 'user_id' => $u2->id]);

        $this->actingAs($admin)->post("/admin/dosen/{$budi->id}/kaprodi")->assertSessionHas('ok');
        $this->assertSame('kaprodi', $u2->fresh()->role);
        $this->assertSame('dosen', $u1->fresh()->role);

        // Kaprodi tetap bisa membuka bimbingannya sendiri dan menu kaprodi.
        $this->actingAs($u2->fresh())->get('/dosen')->assertOk();
        $this->actingAs($u2->fresh())->get('/kelola/judul')->assertOk();
        $this->actingAs($u2->fresh())->get('/beranda')->assertSee('Bimbingan saya');

        $this->actingAs($admin)->delete("/admin/dosen/{$budi->id}/kaprodi");
        $this->assertSame('dosen', $u2->fresh()->role);
    }
}
