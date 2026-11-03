<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\IsianSyarat;
use App\Models\JadwalUjian;
use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\Ruang;
use App\Models\SesiUjian;
use App\Models\Skripsi;
use App\Models\Tahap;
use App\Models\User;
use Database\Seeders\ContohSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjadwalanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(ContohSeeder::class);
        $this->admin = User::where('username', 'admin')->first();
    }

    /** Buat mahasiswa dengan judul disetujui dan seminar proposal terverifikasi. */
    private function siapSempro(string $nim, Dosen $p1, Dosen $p2): Pendaftaran
    {
        $m = Mahasiswa::firstOrCreate(['nim' => $nim], ['nama' => "Mahasiswa {$nim}"]);
        Skripsi::create(['mahasiswa_id' => $m->id, 'judul' => "Judul {$nim}", 'status' => 'disetujui', 'pembimbing_1_id' => $p1->id, 'pembimbing_2_id' => $p2->id]);
        Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => Tahap::where('kode', 'judul')->value('id'), 'status' => 'lulus']);

        return Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => Tahap::where('kode', 'sempro')->value('id'), 'status' => 'terverifikasi']);
    }

    private function buatSesi(string $kode, string $ruang, string $mulai = '08:00', string $selesai = '10:00')
    {
        return $this->actingAs($this->admin)->post(route('kelola.jadwal.sesi.store'), [
            'tahap_id' => Tahap::where('kode', 'sempro')->value('id'), 'kode' => $kode, 'tanggal' => '2026-11-16',
            'jam_mulai' => $mulai, 'jam_selesai' => $selesai, 'ruang_id' => Ruang::where('nama', $ruang)->value('id'), 'kapasitas' => 3,
        ]);
    }

    public function test_ruang_yang_sama_di_jam_beririsan_ditolak(): void
    {
        $this->buatSesi('SP-01', 'Ruang 2.1')->assertSessionHas('ok');
        $this->buatSesi('SP-02', 'Ruang 2.1', '09:00', '11:00')->assertSessionHas('err');
        $this->buatSesi('SP-02', 'Ruang 2.1', '10:00', '12:00')->assertSessionHas('ok'); // bersambung, tidak beririsan
        $this->assertSame(2, SesiUjian::count());
    }

    public function test_penjadwalan_penguji_dan_bentrok_dosen(): void
    {
        [$a, $b, $c, $d, $e, $f] = Dosen::orderBy('id')->get()->all();
        $p1 = $this->siapSempro('1001', $a, $b);
        $p2 = $this->siapSempro('1002', $c, $d);

        $this->buatSesi('SP-01', 'Ruang 2.1');
        $this->buatSesi('SP-02', 'Ruang 2.2');
        [$s1, $s2] = SesiUjian::orderBy('id')->get()->all();

        $this->get(route('kelola.jadwal.sesi', $s1))->assertOk()->assertSee('Mahasiswa 1001');

        // Penguji tidak boleh pembimbing sendiri
        $this->post(route('kelola.jadwal.slot.store', $s1), ['pendaftaran_id' => $p1->id, 'penguji_1_id' => $a->id, 'penguji_2_id' => $e->id])
            ->assertSessionHas('err');

        $this->post(route('kelola.jadwal.slot.store', $s1), ['pendaftaran_id' => $p1->id, 'penguji_1_id' => $e->id, 'penguji_2_id' => $f->id])
            ->assertSessionHas('ok');
        $this->assertSame('dijadwalkan', $p1->fresh()->status);

        // Dosen E sudah menguji di SP-01 pada jam yang sama, jadi tidak boleh di SP-02
        $this->post(route('kelola.jadwal.slot.store', $s2), ['pendaftaran_id' => $p2->id, 'penguji_1_id' => $e->id, 'penguji_2_id' => $a->id])
            ->assertSessionHas('err');
        // Pembimbing A di SP-01 juga tidak boleh menguji di SP-02 pada jam yang sama
        $this->post(route('kelola.jadwal.slot.store', $s2), ['pendaftaran_id' => $p2->id, 'penguji_1_id' => $a->id, 'penguji_2_id' => $b->id])
            ->assertSessionHas('err');
        // Mahasiswa yang sama boleh di sesi yang sama dengan dosen yang sama (presentasi bergantian)
        $p3 = $this->siapSempro('1003', $c, $d);
        $this->post(route('kelola.jadwal.slot.store', $s1), ['pendaftaran_id' => $p3->id, 'penguji_1_id' => $e->id, 'penguji_2_id' => $f->id])
            ->assertSessionHas('ok');

        $this->assertSame(2, JadwalUjian::count());
        $this->get(route('kelola.jadwal.index'))->assertOk()->assertSee('SP-01');
    }

    public function test_hasil_lulus_membuka_sidang_dan_penguji_dipertahankan(): void
    {
        [$a, $b, , , , $f] = Dosen::orderBy('id')->get()->all();
        $e = Dosen::where('nama', 'Siti Rahmah, M.Pd.')->first(); // punya akun dosen
        $mhsUser = User::where('username', '2021130045')->first();
        $m = Mahasiswa::where('nim', '2021130045')->first();
        Skripsi::create(['mahasiswa_id' => $m->id, 'judul' => 'Judul Putri', 'status' => 'disetujui', 'pembimbing_1_id' => $a->id, 'pembimbing_2_id' => $b->id]);
        Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => Tahap::where('kode', 'judul')->value('id'), 'status' => 'lulus']);
        $p = Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => Tahap::where('kode', 'sempro')->value('id'), 'status' => 'terverifikasi']);

        $this->buatSesi('SP-01', 'Ruang 2.1');
        $s = SesiUjian::first();
        $this->post(route('kelola.jadwal.slot.store', $s), ['pendaftaran_id' => $p->id, 'penguji_1_id' => $e->id, 'penguji_2_id' => $f->id]);
        $j = JadwalUjian::first();

        // Mahasiswa belum bisa melihat kartu sebelum jadwal terbit
        $this->actingAs($mhsUser)->get(route('jadwal.kartu', $j))->assertForbidden();
        $this->actingAs($this->admin)->post(route('kelola.jadwal.sesi.terbitkan', $s));
        $this->actingAs($mhsUser)->get(route('jadwal.kartu', $j))->assertOk()->assertSee('KARTU SEMINAR PROPOSAL');
        $this->actingAs($mhsUser)->get(route('mhs.beranda'))->assertSee('Senin, 16-11-2026');

        // Dosen penguji melihat jadwalnya
        $this->actingAs(User::where('username', 'siti')->first())->get(route('dosen.beranda'))->assertSee('SP-01');

        // Kaprodi mencatat hasil lulus, sidang terbuka
        $kaprodi = User::where('username', 'kaprodi')->first();
        $this->actingAs($kaprodi)->post(route('kelola.jadwal.hasil', $j), ['hasil' => 'lulus_revisi'])->assertSessionHas('ok');
        $this->assertSame('lulus', $p->fresh()->status);
        $this->actingAs($mhsUser)->get(route('mhs.tahap', 'sidang'))->assertOk();

        // Sidang: penguji seminar disarankan lagi
        $ps = Pendaftaran::where('tahap_id', Tahap::where('kode', 'sidang')->value('id'))->first();
        $ps->update(['status' => 'terverifikasi']);
        $this->actingAs($this->admin)->post(route('kelola.jadwal.sesi.store'), [
            'tahap_id' => Tahap::where('kode', 'sidang')->value('id'), 'kode' => 'SK-01', 'tanggal' => '2026-12-07',
            'jam_mulai' => '08:00', 'jam_selesai' => '10:00', 'ruang_id' => Ruang::first()->id, 'kapasitas' => 3,
        ]);
        $sk = SesiUjian::where('kode', 'SK-01')->first();
        $html = $this->get(route('kelola.jadwal.sesi', $sk))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/value="'.$e->id.'" selected/', $html);
    }

    public function test_ekspor_csv_berformat_sheet(): void
    {
        [$a, $b, , , $e, $f] = Dosen::orderBy('id')->get()->all();
        $p = $this->siapSempro('1001', $a, $b);
        $this->buatSesi('SP-01', 'Ruang 2.1');
        $this->post(route('kelola.jadwal.slot.store', SesiUjian::first()), ['pendaftaran_id' => $p->id, 'penguji_1_id' => $e->id, 'penguji_2_id' => $f->id]);

        $csv = $this->get(route('kelola.jadwal.ekspor', ['tahap' => 'sempro']))->assertOk()->streamedContent();
        $this->assertStringContainsString('KODE_UJIAN;HARI;TANGGAL;WAKTU', $csv);
        $this->assertStringContainsString('SP-01;Senin;16-11-2026', $csv);
        $this->assertStringContainsString('Mahasiswa 1001', $csv);
    }

    public function test_kaprodi_hanya_melihat_jadwal(): void
    {
        $kaprodi = User::where('username', 'kaprodi')->first();
        $this->actingAs($kaprodi)->get(route('kelola.jadwal.index'))->assertOk();
        $this->actingAs($kaprodi)->post(route('kelola.jadwal.sesi.store'), [])->assertForbidden();
    }
}
