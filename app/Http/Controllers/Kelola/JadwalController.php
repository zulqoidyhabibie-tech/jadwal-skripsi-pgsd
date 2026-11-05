<?php

namespace App\Http\Controllers\Kelola;

use App\Http\Controllers\Controller;
use App\Models\JadwalUjian;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\SesiUjian;
use App\Models\Tahap;
use App\Services\CekBentrok;
use App\Services\PembagianDosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JadwalController extends Controller
{
    public function __construct(private CekBentrok $bentrok, private PembagianDosen $pembagian) {}

    private function tahapDipilih(Request $request): Tahap
    {
        return Tahap::where('kode', $request->query('tahap', 'sempro'))->where('kode', '!=', 'judul')->first()
            ?? Tahap::where('kode', 'sempro')->firstOrFail();
    }

    public function index(Request $request)
    {
        $tahap = $this->tahapDipilih($request);
        $periode = Periode::aktif();
        $sesi = SesiUjian::with('ruang', 'jadwal.pendaftaran.mahasiswa.skripsi.pembimbing1', 'jadwal.pendaftaran.mahasiswa.skripsi.pembimbing2', 'jadwal.penguji1', 'jadwal.penguji2')
            ->where('tahap_id', $tahap->id)
            ->when($periode, fn ($q) => $q->where('periode_id', $periode->id))
            ->orderBy('tanggal')->orderBy('jam_mulai')->get();

        return view('kelola.jadwal.index', [
            'tahap' => $tahap,
            'tahapan' => Tahap::where('kode', '!=', 'judul')->orderBy('urutan')->get(),
            'sesi' => $sesi,
            'bentrok' => $sesi->mapWithKeys(fn ($s) => [$s->id => $this->bentrok->semuaBentrok($s)]),
            'siap' => Pendaftaran::with('mahasiswa')->where('tahap_id', $tahap->id)->where('status', 'terverifikasi')->count(),
            'ruang' => Ruang::orderBy('nama')->get(),
            'kodeBerikut' => $this->kodeBerikut($tahap, $periode),
        ]);
    }

    private function kodeBerikut(Tahap $tahap, ?Periode $periode): string
    {
        $awalan = $tahap->kode === 'sidang' ? 'SK' : 'SP';
        $n = SesiUjian::where('tahap_id', $tahap->id)->where('periode_id', $periode?->id)->count() + 1;

        return sprintf('%s-%02d', $awalan, $n);
    }

    public function buatSesi(Request $request)
    {
        return $this->simpanSesi($request, null);
    }

    public function ubahSesi(Request $request, SesiUjian $sesi)
    {
        return $this->simpanSesi($request, $sesi);
    }

    private function simpanSesi(Request $request, ?SesiUjian $sesi)
    {
        $periode = Periode::aktif();
        $data = $request->validate([
            'tahap_id' => ['required', Rule::exists('tahap', 'id')->whereNot('kode', 'judul')],
            'kode' => ['required', 'string', 'max:30', Rule::unique('sesi_ujian')->where('periode_id', $periode?->id)->ignore($sesi?->id)],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'ruang_id' => ['required', 'exists:ruang,id'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:10'],
        ], ['kode.unique' => 'Kode sesi ini sudah dipakai.', 'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.']);

        $calon = new SesiUjian($data + ['periode_id' => $periode?->id]);
        $calon->id = $sesi?->id;
        if ($pesan = $this->bentrok->bentrokRuang($calon)) {
            return back()->withInput()->with('err', 'Bentrok: '.implode(' ', $pesan));
        }
        if ($sesi) {
            $calon->setRelation('jadwal', $sesi->jadwal);
            $dosen = $sesi->jadwal->flatMap(fn ($j) => $j->dosenHadir())->all();
            if ($pesan = $this->bentrok->bentrokDosen($calon, $dosen)) {
                return back()->withInput()->with('err', 'Bentrok: '.implode(' ', $pesan));
            }
            if ($data['kapasitas'] < $sesi->jadwal->count()) {
                return back()->withInput()->with('err', 'Kapasitas tidak boleh lebih kecil dari jumlah mahasiswa yang sudah dijadwalkan.');
            }
            $sesi->update($data);
        } else {
            SesiUjian::create($data + ['periode_id' => $periode?->id]);
        }

        return redirect()->route('kelola.jadwal.index', ['tahap' => Tahap::find($data['tahap_id'])->kode])
            ->with('ok', $sesi ? "Sesi {$data['kode']} diperbarui." : "Sesi {$data['kode']} dibuat.");
    }

    public function hapusSesi(SesiUjian $sesi)
    {
        DB::transaction(function () use ($sesi) {
            foreach ($sesi->jadwal as $j) {
                $j->pendaftaran->update(['status' => 'terverifikasi']);
            }
            $sesi->delete();
        });

        return back()->with('ok', "Sesi {$sesi->kode} dihapus. Mahasiswanya kembali ke daftar siap dijadwalkan.");
    }

    public function sesi(SesiUjian $sesi)
    {
        $sesi->load('ruang', 'tahap', 'jadwal.pendaftaran.mahasiswa.skripsi.pembimbing1', 'jadwal.pendaftaran.mahasiswa.skripsi.pembimbing2', 'jadwal.penguji1', 'jadwal.penguji2');
        $siap = Pendaftaran::with('mahasiswa.skripsi.pembimbing1', 'mahasiswa.skripsi.pembimbing2')
            ->where('tahap_id', $sesi->tahap_id)->where('status', 'terverifikasi')->orderBy('diverifikasi_pada')->get();

        $pengujiLama = $this->pengujiSeminar($siap);
        $beban = $this->pembagian->bebanPenguji($sesi->periode_id);
        $saran = $siap->mapWithKeys(function ($p) use ($pengujiLama) {
            if (isset($pengujiLama[$p->mahasiswa_id])) {
                return [$p->id => $pengujiLama[$p->mahasiswa_id]];
            }
            $sk = $p->mahasiswa->skripsi;

            return [$p->id => array_map(fn ($d) => $d?->id, $this->pembagian->saranPenguji([$sk?->pembimbing_1_id, $sk?->pembimbing_2_id]))];
        });

        return view('kelola.jadwal.sesi', [
            'sesi' => $sesi,
            'siap' => $siap,
            'saran' => $saran,
            'kandidat1' => $this->pembagian->kandidatPenguji(1, [], $beban),
            'kandidat2' => $this->pembagian->kandidatPenguji(2, [], $beban),
            'bentrok' => $this->bentrok->semuaBentrok($sesi),
            'ruang' => Ruang::orderBy('nama')->get(),
        ]);
    }

    /** Untuk sidang: penguji dipertahankan dari seminar proposal. */
    private function pengujiSeminar($daftar): array
    {
        $sempro = Tahap::where('kode', 'sempro')->value('id');

        return JadwalUjian::whereHas('pendaftaran', fn ($q) => $q->where('tahap_id', $sempro)
            ->whereIn('mahasiswa_id', $daftar->pluck('mahasiswa_id')))
            ->with('pendaftaran')->latest('id')->get()
            ->mapWithKeys(fn ($j) => [$j->pendaftaran->mahasiswa_id => [$j->penguji_1_id, $j->penguji_2_id]])
            ->all();
    }

    public function tambahSlot(Request $request, SesiUjian $sesi)
    {
        $data = $request->validate([
            'pendaftaran_id' => ['required', Rule::exists('pendaftaran', 'id')->where('tahap_id', $sesi->tahap_id)->where('status', 'terverifikasi')],
            'penguji_1_id' => ['required', Rule::exists('dosen', 'id')->where('boleh_penguji', true)],
            'penguji_2_id' => ['required', 'different:penguji_1_id', Rule::exists('dosen', 'id')->where('boleh_penguji', true)],
        ], [
            'pendaftaran_id.exists' => 'Mahasiswa ini belum terverifikasi atau sudah dijadwalkan.',
            'penguji_2_id.different' => 'Penguji 1 dan penguji 2 harus dosen yang berbeda.',
        ]);

        if ($sesi->sisaSlot() < 1) {
            return back()->with('err', "Sesi {$sesi->kode} sudah penuh.");
        }
        $p = Pendaftaran::with('mahasiswa.skripsi')->find($data['pendaftaran_id']);
        $sk = $p->mahasiswa->skripsi;
        $pembimbing = array_filter([$sk?->pembimbing_1_id, $sk?->pembimbing_2_id]);
        if (array_intersect([(int) $data['penguji_1_id'], (int) $data['penguji_2_id']], $pembimbing)) {
            return back()->withInput()->with('err', 'Penguji tidak boleh pembimbing mahasiswa yang sama.');
        }
        $hadir = array_merge($pembimbing, [(int) $data['penguji_1_id'], (int) $data['penguji_2_id']]);
        if ($pesan = $this->bentrok->bentrokDosen($sesi, $hadir)) {
            return back()->withInput()->with('err', 'Bentrok: '.implode(' ', $pesan));
        }

        DB::transaction(function () use ($sesi, $data, $p) {
            JadwalUjian::create($data + ['sesi_ujian_id' => $sesi->id]);
            $p->update(['status' => 'dijadwalkan']);
        });

        return back()->with('ok', "{$p->mahasiswa->nama} dijadwalkan di sesi {$sesi->kode}.");
    }

    public function hapusSlot(JadwalUjian $jadwal)
    {
        abort_if($jadwal->hasil, 403, 'Hasil ujian sudah dicatat.');
        DB::transaction(function () use ($jadwal) {
            $jadwal->pendaftaran->update(['status' => 'terverifikasi']);
            $jadwal->delete();
        });

        return back()->with('ok', 'Mahasiswa dikeluarkan dari sesi dan kembali ke daftar siap dijadwalkan.');
    }

    public function terbitkan(SesiUjian $sesi)
    {
        $sesi->update(['terbit' => ! $sesi->terbit]);

        return back()->with('ok', $sesi->terbit
            ? "Sesi {$sesi->kode} diterbitkan. Mahasiswa dan dosen sekarang bisa melihatnya."
            : "Sesi {$sesi->kode} ditarik kembali menjadi draf.");
    }

    public function hasil(Request $request, JadwalUjian $jadwal)
    {
        $data = $request->validate([
            'hasil' => ['required', Rule::in(array_keys(JadwalUjian::HASIL))],
            'catatan_hasil' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($jadwal, $data) {
            $jadwal->update($data);
            $jadwal->pendaftaran->update(['status' => $data['hasil'] === 'mengulang' ? 'mengulang' : 'lulus']);
        });

        return back()->with('ok', "Hasil {$jadwal->pendaftaran->mahasiswa->nama}: ".JadwalUjian::HASIL[$data['hasil']].'.');
    }

    public function kartu(Request $request, JadwalUjian $jadwal)
    {
        $u = $request->user();
        $milikSendiri = $u->hasRole('mahasiswa') && $u->mahasiswa?->id === $jadwal->pendaftaran->mahasiswa_id;
        abort_unless($u->bisaKelolaSkripsi() || ($milikSendiri && $jadwal->sesi->terbit), 403);

        return view('kelola.jadwal.kartu', [
            'j' => $jadwal->load('sesi.ruang', 'sesi.tahap', 'pendaftaran.mahasiswa.skripsi.pembimbing1', 'pendaftaran.mahasiswa.skripsi.pembimbing2', 'penguji1', 'penguji2'),
        ]);
    }

    /** Ekspor CSV dengan susunan kolom seperti Sheet lama; bisa dibuka di Excel. */
    public function ekspor(Request $request)
    {
        $tahap = $this->tahapDipilih($request);
        $periode = Periode::aktif();
        $sesi = SesiUjian::with('ruang', 'jadwal.pendaftaran.mahasiswa.skripsi.pembimbing1', 'jadwal.pendaftaran.mahasiswa.skripsi.pembimbing2', 'jadwal.penguji1', 'jadwal.penguji2')
            ->where('tahap_id', $tahap->id)->when($periode, fn ($q) => $q->where('periode_id', $periode->id))
            ->orderBy('tanggal')->orderBy('jam_mulai')->get();
        $maks = max(3, (int) $sesi->max('kapasitas'));

        $judul = ['KODE_UJIAN', 'HARI', 'TANGGAL', 'WAKTU'];
        for ($i = 1; $i <= $maks; $i++) {
            array_push($judul, "NAMA MHS {$i}", 'NIM', 'DOSPEM 1', 'DOSPEM 2', 'DOSPENG 1', 'DOSPENG 2', 'RUANG');
        }
        $baris = [$judul];
        foreach ($sesi as $s) {
            $r = [$s->kode, $s->hari(), $s->tanggal->format('d-m-Y'), $s->waktu()];
            foreach ($s->jadwal as $j) {
                $m = $j->pendaftaran->mahasiswa;
                array_push($r, $m->nama, $m->nim, $m->skripsi?->pembimbing1?->nama, $m->skripsi?->pembimbing2?->nama, $j->penguji1->nama, $j->penguji2->nama, $s->ruang->nama);
            }
            $baris[] = $r;
        }

        $nama = 'jadwal-'.$tahap->kode.'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca huruf dengan benar
            foreach ($baris as $b) {
                fputcsv($out, $b, ';');
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
