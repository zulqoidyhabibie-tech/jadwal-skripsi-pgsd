<?php

namespace App\Http\Controllers;

use App\Models\IsianSyarat;
use App\Models\Skripsi;
use App\Models\Tahap;
use App\Services\AlurSkripsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MahasiswaPortalController extends Controller
{
    public function __construct(private AlurSkripsi $alur) {}

    private function mhs(Request $request)
    {
        return $request->user()->mahasiswa ?? abort(403, 'Akun ini belum terhubung ke data mahasiswa. Hubungi admin.');
    }

    public function beranda(Request $request)
    {
        $mhs = $this->mhs($request);
        $tahapan = Tahap::orderBy('urutan')->get()->map(fn (Tahap $t) => [
            'tahap' => $t,
            'terbuka' => $this->alur->tahapTerbuka($mhs, $t),
            'pendaftaran' => $this->alur->pendaftaranTerakhir($mhs, $t),
        ]);

        return view('mahasiswa.beranda', [
            'mhs' => $mhs->load('skripsi.pembimbing1', 'skripsi.pembimbing2'),
            'tahapan' => $tahapan,
        ]);
    }

    public function tahap(Request $request, string $kode)
    {
        $mhs = $this->mhs($request);
        $tahap = Tahap::where('kode', $kode)->firstOrFail();
        if (! $this->alur->tahapTerbuka($mhs, $tahap)) {
            return redirect()->route('mhs.beranda')->with('err', "Tahap {$tahap->nama} belum terbuka. Selesaikan tahap sebelumnya dulu.");
        }
        $p = $this->alur->pendaftaranAktif($mhs, $tahap, buat: true);

        return view('mahasiswa.tahap', [
            'mhs' => $mhs,
            'tahap' => $tahap,
            'pendaftaran' => $p,
            'cek' => $this->alur->cekSyarat($p),
            'skripsi' => $mhs->skripsi,
        ]);
    }

    public function simpan(Request $request, string $kode)
    {
        $mhs = $this->mhs($request);
        $tahap = Tahap::where('kode', $kode)->firstOrFail();
        abort_unless($this->alur->tahapTerbuka($mhs, $tahap), 403, 'Tahap ini belum terbuka.');
        $p = $this->alur->pendaftaranAktif($mhs, $tahap, buat: true);
        abort_unless($p && $p->bisaDiubah(), 403, 'Pendaftaran ini sudah diajukan dan tidak bisa diubah.');

        $rules = ['isian' => ['array'], 'isian.*' => ['nullable', 'string', 'max:1000']];
        if ($kode === 'judul') {
            $rules['judul'] = ['required', 'string', 'min:10', 'max:500'];
        }
        $data = $request->validate($rules, [], ['judul' => 'judul skripsi']);

        DB::transaction(function () use ($data, $p, $tahap, $mhs, $kode) {
            foreach ($tahap->syarat as $s) {
                $nilai = $s->jenis === 'centang'
                    ? (! empty($data['isian'][$s->id]) ? '1' : null)
                    : trim((string) ($data['isian'][$s->id] ?? ''));
                $lama = IsianSyarat::where('pendaftaran_id', $p->id)->where('syarat_id', $s->id)->first();
                // Isian yang tidak berubah dan sudah diterima tetap diterima.
                $status = ($lama && $lama->nilai === $nilai && $lama->status === 'diterima') ? 'diterima' : 'menunggu';
                IsianSyarat::updateOrCreate(
                    ['pendaftaran_id' => $p->id, 'syarat_id' => $s->id],
                    ['nilai' => $nilai ?: null, 'status' => $status, 'catatan' => $status === 'diterima' ? $lama->catatan : null],
                );
            }
            if ($kode === 'judul') {
                $sk = $mhs->skripsi;
                if ($sk && $sk->status !== 'disetujui') {
                    $sk->update(['judul' => $data['judul']]);
                } elseif (! $sk) {
                    Skripsi::create(['mahasiswa_id' => $mhs->id, 'judul' => $data['judul'], 'status' => 'draf']);
                }
            }
        });

        if ($request->input('aksi') === 'ajukan') {
            return $this->ajukan($request, $kode);
        }

        return back()->with('ok', 'Isian tersimpan.');
    }

    public function ajukan(Request $request, string $kode)
    {
        $mhs = $this->mhs($request)->fresh(); // muat ulang: judul mungkin baru saja dibuat
        $tahap = Tahap::where('kode', $kode)->firstOrFail();
        $p = $this->alur->pendaftaranAktif($mhs, $tahap);
        abort_unless($p && $p->bisaDiubah(), 403);

        if (! $this->alur->cekSyarat($p->fresh())['lengkap']) {
            return back()->with('err', 'Masih ada syarat wajib yang belum terpenuhi.');
        }
        if ($kode === 'judul' && ! $mhs->skripsi) {
            return back()->with('err', 'Isi judul skripsi terlebih dahulu.');
        }

        DB::transaction(function () use ($p, $mhs, $kode) {
            $p->update(['status' => 'diajukan', 'diajukan_pada' => now(), 'catatan' => null]);
            if ($kode === 'judul') {
                $mhs->skripsi->update(['status' => 'diajukan', 'catatan' => null]);
            }
        });

        return redirect()->route('mhs.beranda')->with('ok', "Pendaftaran {$tahap->nama} terkirim. Kaprodi akan memverifikasinya.");
    }
}
