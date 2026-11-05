<?php

namespace App\Http\Controllers\Kelola;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Pendaftaran;
use App\Services\AlurSkripsi;
use App\Services\PembagianDosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JudulController extends Controller
{
    public function __construct(private AlurSkripsi $alur, private PembagianDosen $pembagian) {}

    public function index()
    {
        $daftar = Pendaftaran::with('mahasiswa.skripsi')
            ->whereHas('tahap', fn ($q) => $q->where('kode', 'judul'))
            ->where('status', 'diajukan')->orderBy('diajukan_pada')->get();

        $beban = $this->pembagian->bebanBimbingan();

        return view('kelola.judul', [
            'daftar' => $daftar,
            'cek' => $daftar->mapWithKeys(fn ($p) => [$p->id => $this->alur->cekSyarat($p)]),
            'kandidat1' => $this->pembagian->kandidatPembimbing(1, $beban),
            'kandidat2' => $this->pembagian->kandidatPembimbing(2, $beban),
            'saran' => $this->pembagian->saranPembimbing(),
        ]);
    }

    public function setujui(Request $request, Pendaftaran $pendaftaran)
    {
        abort_unless($pendaftaran->tahap->kode === 'judul' && $pendaftaran->status === 'diajukan', 404);

        $data = $request->validate([
            'pembimbing_1_id' => ['required', Rule::exists('dosen', 'id')->where('boleh_pembimbing_1', true)],
            'pembimbing_2_id' => ['required', 'different:pembimbing_1_id', Rule::exists('dosen', 'id')->where('boleh_pembimbing_2', true)],
        ], [
            'pembimbing_2_id.different' => 'Pembimbing 1 dan pembimbing 2 harus dosen yang berbeda.',
        ]);

        DB::transaction(function () use ($pendaftaran, $data) {
            $pendaftaran->mahasiswa->skripsi->update($data + ['status' => 'disetujui', 'catatan' => null]);
            $pendaftaran->isian()->update(['status' => 'diterima']);
            $pendaftaran->update(['status' => 'lulus', 'diverifikasi_pada' => now()]);
        });

        $p1 = Dosen::find($data['pembimbing_1_id'])->nama;
        $p2 = Dosen::find($data['pembimbing_2_id'])->nama;

        return back()->with('ok', "Judul {$pendaftaran->mahasiswa->nama} disetujui. Pembimbing: {$p1} dan {$p2}.");
    }

    public function revisi(Request $request, Pendaftaran $pendaftaran)
    {
        abort_unless($pendaftaran->tahap->kode === 'judul' && $pendaftaran->status === 'diajukan', 404);
        $data = $request->validate(['catatan' => ['required', 'string', 'max:1000']], [], ['catatan' => 'catatan revisi']);

        DB::transaction(function () use ($pendaftaran, $data) {
            $pendaftaran->update(['status' => 'ditolak', 'catatan' => $data['catatan']]);
            $pendaftaran->mahasiswa->skripsi?->update(['status' => 'revisi', 'catatan' => $data['catatan']]);
        });

        return back()->with('ok', "Judul {$pendaftaran->mahasiswa->nama} dikembalikan untuk revisi.");
    }
}
