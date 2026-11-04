<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\User;
use App\Support\Csv;
use App\Support\Tabel;
use Illuminate\Http\Request;

class DosenController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $dosen = Dosen::when($q, fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('nomor_identitas', 'like', "%{$q}%"))
            ->with('user')->orderBy('nama')->paginate(30)->withQueryString();

        return view('admin.dosen.index', compact('dosen', 'q'));
    }

    public function create()
    {
        return view('admin.dosen.form', ['dosen' => new Dosen(['aktif' => true, 'boleh_pembimbing_1' => true, 'boleh_pembimbing_2' => true, 'boleh_penguji' => true])]);
    }

    public function store(Request $request)
    {
        Dosen::create($this->data($request));

        return redirect()->route('admin.dosen.index')->with('ok', 'Dosen ditambahkan.');
    }

    public function edit(Dosen $dosen)
    {
        return view('admin.dosen.form', compact('dosen'));
    }

    public function update(Request $request, Dosen $dosen)
    {
        $dosen->update($this->data($request));

        return redirect()->route('admin.dosen.index')->with('ok', 'Data dosen diperbarui.');
    }

    public function destroy(Dosen $dosen)
    {
        $dosen->update(['aktif' => false]);

        return back()->with('ok', "{$dosen->nama} dinonaktifkan. Riwayat bimbingannya tetap tersimpan.");
    }

    public function import(Request $request)
    {
        $request->validate(['file' => Tabel::ATURAN_BERKAS], ['file.mimes' => 'Berkas harus Excel (.xlsx) atau CSV.']);
        $baris = Tabel::baca($request->file('file'));
        $n = 0;
        foreach ($baris as $r) {
            $nama = trim($r['nama'] ?? '');
            if ($nama === '') {
                continue;
            }
            $no = trim($r['nomor_identitas'] ?? '') ?: null;
            $attrs = [
                'nama' => $nama,
                'nomor_identitas' => $no,
                'jenis_identitas' => strtoupper(trim($r['jenis_identitas'] ?? '')) ?: ($no ? (strlen($no) > 10 ? 'NUPTK' : 'NIDN') : null),
                'boleh_pembimbing_1' => Csv::ya($r['boleh_p1'] ?? '1'),
                'boleh_pembimbing_2' => Csv::ya($r['boleh_p2'] ?? '1'),
                'boleh_penguji' => Csv::ya($r['boleh_penguji'] ?? '1'),
                'kuota_pembimbing_1' => (int) ($r['kuota_p1'] ?? 0),
                'kuota_pembimbing_2' => (int) ($r['kuota_p2'] ?? 0),
                'kuota_penguji_1' => (int) ($r['kuota_u1'] ?? 0),
                'kuota_penguji_2' => (int) ($r['kuota_u2'] ?? 0),
                'aktif' => true,
            ];
            Dosen::updateOrCreate(['nama' => $nama], $attrs);
            $n++;
        }

        return back()->with('ok', "{$n} data dosen diimpor.");
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis_identitas' => ['nullable', 'in:NIDN,NUPTK'],
            'nomor_identitas' => ['nullable', 'string', 'max:30'],
            'bidang_keahlian' => ['nullable', 'string', 'max:255'],
            'kuota_pembimbing_1' => ['required', 'integer', 'min:0', 'max:99'],
            'kuota_pembimbing_2' => ['required', 'integer', 'min:0', 'max:99'],
            'kuota_penguji_1' => ['required', 'integer', 'min:0', 'max:99'],
            'kuota_penguji_2' => ['required', 'integer', 'min:0', 'max:99'],
        ]);
        foreach (['boleh_pembimbing_1', 'boleh_pembimbing_2', 'boleh_penguji', 'aktif'] as $f) {
            $data[$f] = $request->boolean($f);
        }

        return $data;
    }

    public function template()
    {
        return Tabel::template('template-dosen.xlsx', [
            'nama' => 'Nama lengkap dengan gelar. Dosen dengan nama yang sama akan diperbarui.',
            'jenis_identitas' => 'NIDN atau NUPTK. Boleh kosong.',
            'nomor_identitas' => 'Nomor NIDN/NUPTK. Boleh kosong, bisa dilengkapi nanti.',
            'boleh_p1' => 'Boleh menjadi pembimbing 1: ya / tidak.',
            'boleh_p2' => 'Boleh menjadi pembimbing 2: ya / tidak.',
            'boleh_penguji' => 'Boleh menjadi penguji: ya / tidak.',
            'kuota_p1' => 'Kuota mahasiswa sebagai pembimbing 1. Isi 0 bila tanpa batas.',
            'kuota_p2' => 'Kuota mahasiswa sebagai pembimbing 2. Isi 0 bila tanpa batas.',
            'kuota_u1' => 'Kuota sebagai penguji 1 per periode. Isi 0 bila tanpa batas.',
            'kuota_u2' => 'Kuota sebagai penguji 2 per periode. Isi 0 bila tanpa batas.',
        ], [
            ['Dr. Contoh Dosen, M.Pd.', 'NIDN', '1012345678', 'ya', 'ya', 'ya', 8, 8, 10, 10],
            ['Contoh Dosen Kedua, M.Pd.', '', '', 'tidak', 'ya', 'ya', 0, 6, 0, 10],
        ]);
    }

    /** Jadikan dosen ini kaprodi memakai akun dosennya sendiri; kaprodi sebelumnya kembali menjadi dosen. */
    public function jadikanKaprodi(Dosen $dosen)
    {
        abort_unless($dosen->user, 422, 'Dosen ini belum punya akun.');
        $lama = User::where('role', 'kaprodi')->whereKeyNot($dosen->user_id)->get();
        User::whereIn('id', $lama->pluck('id'))->update(['role' => 'dosen']);
        $dosen->user->update(['role' => 'kaprodi', 'is_active' => true]);

        $pesan = "{$dosen->nama} sekarang menjadi kaprodi dan tetap bisa melihat bimbingannya.";
        if ($lama->isNotEmpty()) {
            $pesan .= ' Kaprodi sebelumnya ('.$lama->pluck('name')->join(', ').') kembali berperan sebagai dosen.';
        }

        return back()->with('ok', $pesan);
    }

    public function lepasKaprodi(Dosen $dosen)
    {
        abort_unless($dosen->user && $dosen->user->role === 'kaprodi', 422);
        $dosen->user->update(['role' => 'dosen']);

        return back()->with('ok', "{$dosen->nama} kembali berperan sebagai dosen.");
    }
}
