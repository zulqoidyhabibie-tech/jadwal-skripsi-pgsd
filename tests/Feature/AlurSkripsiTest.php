<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\Syarat;
use App\Models\Tahap;
use App\Models\User;
use Database\Seeders\ContohSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlurSkripsiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(ContohSeeder::class);
    }

    private function isiSyarat(string $kode, array $override = []): array
    {
        $isian = [];
        foreach (Tahap::where('kode', $kode)->first()->syarat as $s) {
            $isian[$s->id] = $override[$s->nama] ?? match ($s->jenis) {
                'angka' => '150',
                'link' => 'https://drive.google.com/file/d/contoh',
                'centang' => '1',
            };
        }

        return $isian;
    }

    public function test_mahasiswa_mendaftar_lalu_menunggu_aktivasi(): void
    {
        $this->post('/daftar', [
            'nim' => '2021130012', 'nama' => 'Rizki Pratama', 'email' => 'rizki@contoh.local',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/masuk');

        $this->post('/masuk', ['login' => '2021130012', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();

        $admin = User::where('username', 'admin')->first();
        $baru = User::where('username', '2021130012')->first();
        $this->actingAs($admin)->post(route('admin.pengguna.aktifkan', $baru))->assertRedirect();
        $this->assertTrue($baru->fresh()->is_active);
    }

    public function test_nim_yang_tidak_terdata_ditolak(): void
    {
        $this->post('/daftar', [
            'nim' => '9999999999', 'nama' => 'Orang Lain', 'email' => 'x@contoh.local',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('nim');
        $this->assertDatabaseMissing('users', ['username' => '9999999999']);
    }

    public function test_tombol_daftar_tidak_jalan_bila_syarat_kurang(): void
    {
        $mhs = User::where('username', '2021130045')->first();
        $this->actingAs($mhs)->get(route('mhs.tahap', 'judul'))->assertOk();

        $isian = $this->isiSyarat('judul', ['SKS lulus' => '80']); // di bawah minimal 100
        $this->post(route('mhs.simpan', 'judul'), ['judul' => 'Penerapan model PBL di kelas IV SD', 'isian' => $isian, 'aksi' => 'ajukan'])
            ->assertSessionHas('err');
        $putri = Mahasiswa::where('nim', '2021130045')->first();
        $this->assertSame('draf', $putri->pendaftaran()->first()->status);
    }

    public function test_alur_judul_sampai_seminar_proposal_terverifikasi(): void
    {
        $mhsUser = User::where('username', '2021130045')->first();
        $mhs = Mahasiswa::where('nim', '2021130045')->first();

        // Seminar proposal terkunci sebelum judul disetujui
        $this->actingAs($mhsUser)->get(route('mhs.tahap', 'sempro'))->assertRedirect(route('mhs.beranda'));

        // Ajukan judul
        $this->post(route('mhs.simpan', 'judul'), ['judul' => 'Penerapan model PBL di kelas IV SD', 'isian' => $this->isiSyarat('judul'), 'aksi' => 'ajukan'])
            ->assertRedirect(route('mhs.beranda'));
        $pJudul = $mhs->pendaftaran()->first();
        $this->assertSame('diajukan', $pJudul->status);

        // Kaprodi tidak boleh memilih pembimbing yang sama
        $kaprodi = User::where('username', 'kaprodi')->first();
        $d1 = Dosen::first();
        $this->actingAs($kaprodi)->get(route('kelola.judul.index'))->assertOk()->assertSee('Penerapan model PBL');
        $this->post(route('kelola.judul.setujui', $pJudul), ['pembimbing_1_id' => $d1->id, 'pembimbing_2_id' => $d1->id])
            ->assertSessionHasErrors('pembimbing_2_id');

        $d2 = Dosen::skip(1)->first();
        $this->post(route('kelola.judul.setujui', $pJudul), ['pembimbing_1_id' => $d1->id, 'pembimbing_2_id' => $d2->id])->assertRedirect();
        $this->assertSame('lulus', $pJudul->fresh()->status);
        $this->assertSame($d1->id, $mhs->skripsi->pembimbing_1_id);

        // Seminar proposal sekarang terbuka; ajukan
        $this->actingAs($mhsUser)->get(route('mhs.tahap', 'sempro'))->assertOk();
        $this->post(route('mhs.simpan', 'sempro'), ['isian' => $this->isiSyarat('sempro'), 'aksi' => 'ajukan'])->assertRedirect(route('mhs.beranda'));
        $pSempro = $mhs->pendaftaran()->where('tahap_id', Tahap::where('kode', 'sempro')->value('id'))->first();
        $this->assertSame('diajukan', $pSempro->status);

        // Kaprodi menolak satu syarat -> kembali ke mahasiswa
        $isian = $pSempro->isian;
        $status = $isian->mapWithKeys(fn ($i) => [$i->id => 'diterima'])->all();
        $status[$isian->first()->id] = 'ditolak';
        $this->actingAs($kaprodi)->post(route('kelola.verifikasi.putuskan', $pSempro), ['status' => $status])->assertRedirect();
        $this->assertSame('ditolak', $pSempro->fresh()->status);

        // Mahasiswa memperbaiki dan mengajukan lagi, lalu diterima
        $this->actingAs($mhsUser)->post(route('mhs.simpan', 'sempro'), ['isian' => $this->isiSyarat('sempro', ['SKS lulus' => '151']), 'aksi' => 'ajukan']);
        $this->assertSame('diajukan', $pSempro->fresh()->status);
        $semua = $pSempro->fresh()->isian->mapWithKeys(fn ($i) => [$i->id => 'diterima'])->all();
        $this->actingAs($kaprodi)->post(route('kelola.verifikasi.putuskan', $pSempro), ['status' => $semua]);
        $this->assertSame('terverifikasi', $pSempro->fresh()->status);
    }

    public function test_peran_dibatasi(): void
    {
        $mhs = User::where('username', '2021130045')->first();
        $this->actingAs($mhs)->get(route('kelola.judul.index'))->assertForbidden();
        $this->actingAs($mhs)->get(route('admin.dosen.index'))->assertForbidden();

        $kaprodi = User::where('username', 'kaprodi')->first();
        $this->actingAs($kaprodi)->get(route('admin.dosen.index'))->assertForbidden();
        $this->actingAs($kaprodi)->get(route('kelola.rekap'))->assertOk();
    }

    public function test_admin_impor_dosen_dari_csv(): void
    {
        $admin = User::where('username', 'admin')->first();
        $csv = "nama;jenis_identitas;nomor_identitas;boleh_p1;boleh_p2;boleh_penguji;kuota_p1;kuota_p2;kuota_u1;kuota_u2\n"
            ."Reni Guswita, M.Pd.;;;0;1;1;0;6;6;6\nAprinaldo, M.Pd;;536768669130352;1;1;1;7;6;6;6\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('dosen.csv', $csv);
        $this->actingAs($admin)->post(route('admin.dosen.import'), ['file' => $file])->assertSessionHas('ok');

        $reni = Dosen::where('nama', 'Reni Guswita, M.Pd.')->first();
        $this->assertNull($reni->nomor_identitas);
        $this->assertFalse($reni->boleh_pembimbing_1);
        $this->assertSame('NUPTK', Dosen::where('nama', 'Aprinaldo, M.Pd')->value('jenis_identitas'));
    }

    public function test_halaman_admin_terbuka(): void
    {
        $admin = User::where('username', 'admin')->first();
        foreach (['beranda', 'admin.pengguna.index', 'admin.mahasiswa.index', 'admin.dosen.index', 'admin.dosen.create', 'admin.ruang.index', 'admin.syarat.index', 'admin.periode.index', 'kelola.verifikasi.index', 'kelola.rekap'] as $r) {
            $this->actingAs($admin)->get(route($r))->assertOk();
        }
        $this->actingAs(User::where('username', 'siti')->first())->get(route('dosen.beranda'))->assertOk();
    }
}
