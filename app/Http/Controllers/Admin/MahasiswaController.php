<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Pendaftaran;
use App\Models\Skripsi;
use App\Models\Tahap;
use App\Support\Tabel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MahasiswaController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $mahasiswa = Mahasiswa::with('user', 'skripsi')
            ->when($q, fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('nim', 'like', "%{$q}%"))
            ->orderBy('nim')->paginate(30)->withQueryString();

        return view('admin.mahasiswa.index', compact('mahasiswa', 'q'));
    }

    public function create()
    {
        return view('admin.mahasiswa.form', ['mahasiswa' => new Mahasiswa]);
    }

    public function store(Request $request)
    {
        Mahasiswa::create($this->data($request));

        return redirect()->route('admin.mahasiswa.index')->with('ok', 'Mahasiswa ditambahkan.');
    }

    public function edit(Mahasiswa $mahasiswa)
    {
        return view('admin.mahasiswa.form', compact('mahasiswa'));
    }

    public function update(Request $request, Mahasiswa $mahasiswa)
    {
        $mahasiswa->update($this->data($request, $mahasiswa));

        return redirect()->route('admin.mahasiswa.index')->with('ok', 'Data mahasiswa diperbarui.');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => Tabel::ATURAN_BERKAS], ['file.mimes' => 'Berkas harus Excel (.xlsx) atau CSV.']);
        $dosen = Dosen::all();
        $tahapJudul = Tahap::where('kode', 'judul')->value('id');
        $n = 0;
        $skripsi = 0;
        $takDikenal = [];

        foreach (Tabel::baca($request->file('file')) as $r) {
            // Terima nama kolom dari aplikasi ini maupun dari tab DATA MHS di Sheet lama.
            $nim = trim($r['nim'] ?? $r['npm'] ?? '');
            $nama = trim($r['nama'] ?? $r['nama_lengkap'] ?? '');
            if ($nim === '' || $nama === '') {
                continue;
            }
            $m = Mahasiswa::updateOrCreate(['nim' => $nim], array_filter([
                'nama' => $nama,
                'angkatan' => trim($r['angkatan'] ?? '') ?: null,
                'no_hp' => self::rapikanHp($r['no_hp'] ?? $r['no._hp'] ?? $r['no_wa'] ?? ''),
            ], fn ($v) => $v !== null));
            $n++;

            // Mahasiswa yang sudah punya judul dan pembimbing di Sheet: catat sebagai judul disetujui.
            $judul = trim($r['judul_skripsi_final'] ?? '') ?: trim($r['judul_skripsi'] ?? $r['judul'] ?? '');
            $p1 = $this->cariDosen($dosen, $r['pembimbing_1'] ?? '');
            $p2 = $this->cariDosen($dosen, $r['pembimbing_2'] ?? '');
            foreach ([['pembimbing_1', $p1], ['pembimbing_2', $p2]] as [$kolom, $hasil]) {
                if (trim($r[$kolom] ?? '') !== '' && ! $hasil) {
                    $takDikenal[] = trim($r[$kolom]);
                }
            }
            if ($judul !== '' && $p1 && $p2 && ! $m->skripsi()->exists()) {
                Skripsi::create(['mahasiswa_id' => $m->id, 'judul' => mb_substr($judul, 0, 500), 'status' => 'disetujui',
                    'pembimbing_1_id' => $p1->id, 'pembimbing_2_id' => $p2->id]);
                Pendaftaran::create(['mahasiswa_id' => $m->id, 'tahap_id' => $tahapJudul, 'status' => 'lulus',
                    'catatan' => 'Diimpor dari data lama', 'diverifikasi_pada' => now()]);
                $skripsi++;
            }
        }

        $pesan = "{$n} data mahasiswa diimpor";
        $pesan .= $skripsi ? ", {$skripsi} di antaranya beserta judul dan pembimbing." : '.';
        if ($takDikenal) {
            $pesan .= ' Nama pembimbing tidak dikenali: '.implode(', ', array_unique($takDikenal)).'. Periksa ejaannya di data dosen.';
        }

        return back()->with($takDikenal ? 'err' : 'ok', $pesan);
    }

    /** Cocokkan nama dosen dari Sheet dengan data dosen, mengabaikan gelar, tanda baca, dan huruf besar. */
    private function cariDosen($dosen, ?string $nama): ?Dosen
    {
        $kunci = self::kunciNama((string) $nama);
        if ($kunci === '') {
            return null;
        }

        return $dosen->first(fn ($d) => self::kunciNama($d->nama) === $kunci);
    }

    public static function kunciNama(string $nama): string
    {
        $nama = mb_strtolower($nama);
        $nama = preg_replace('/,.*$/u', '', $nama); // buang gelar di belakang koma
        $nama = preg_replace('/\b(dr|prof|drs|ir|h|hj)\.\s*/u', '', $nama); // buang gelar depan
        $nama = preg_replace('/[^a-z\s]/u', ' ', $nama);

        return trim(preg_replace('/\s+/', ' ', $nama));
    }

    private function data(Request $request, ?Mahasiswa $m = null): array
    {
        return $request->validate([
            'nim' => ['required', 'string', 'max:30', Rule::unique('mahasiswa', 'nim')->ignore($m?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'angkatan' => ['nullable', 'digits:4'],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /** Excel membuang angka 0 di depan nomor HP; kembalikan bila hilang. */
    public static function rapikanHp(string $hp): ?string
    {
        $hp = preg_replace('/[^0-9+]/', '', $hp);
        if ($hp === '') {
            return null;
        }

        return str_starts_with($hp, '8') ? '0'.$hp : $hp;
    }

    public function template()
    {
        return Tabel::template('template-mahasiswa.xlsx', [
            'nim' => 'NIM / NPM mahasiswa. NIM yang sudah ada akan diperbarui.',
            'nama' => 'Nama lengkap mahasiswa.',
            'angkatan' => 'Tahun masuk, misalnya 2022.',
            'no_hp' => 'Nomor HP/WA. Boleh kosong.',
            'pembimbing_1' => 'Isi hanya bila judul sudah disetujui. Tulis nama dosen persis seperti di Data dosen.',
            'pembimbing_2' => 'Isi hanya bila judul sudah disetujui.',
            'judul_skripsi' => 'Isi hanya bila judul sudah disetujui. Bila judul dan kedua pembimbing terisi, judul langsung tercatat disetujui.',
        ], [
            ['2022130001', 'Contoh Mahasiswa', '2022', '081234567890', '', '', ''],
        ]);
    }
}
