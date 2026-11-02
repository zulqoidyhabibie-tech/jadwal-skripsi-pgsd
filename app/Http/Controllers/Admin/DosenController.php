<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Support\Csv;
use Illuminate\Http\Request;

class DosenController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $dosen = Dosen::when($q, fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('nomor_identitas', 'like', "%{$q}%"))
            ->orderBy('nama')->paginate(30)->withQueryString();

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
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $baris = Csv::baca($request->file('file')->getRealPath());
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
}
