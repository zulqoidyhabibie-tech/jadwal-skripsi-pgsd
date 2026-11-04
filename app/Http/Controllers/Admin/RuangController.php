<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ruang;
use App\Support\Tabel;
use Illuminate\Http\Request;

class RuangController extends Controller
{
    public function index()
    {
        return view('admin.ruang', ['ruang' => Ruang::orderBy('nama')->get()]);
    }

    public function store(Request $request)
    {
        Ruang::create($request->validate(['nama' => ['required', 'string', 'max:100'], 'kapasitas' => ['nullable', 'integer', 'min:1']]));

        return back()->with('ok', 'Ruang ditambahkan.');
    }

    public function update(Request $request, Ruang $ruang)
    {
        $ruang->update($request->validate(['nama' => ['required', 'string', 'max:100'], 'kapasitas' => ['nullable', 'integer', 'min:1']]));

        return back()->with('ok', 'Ruang diperbarui.');
    }

    public function destroy(Ruang $ruang)
    {
        $ruang->delete();

        return back()->with('ok', 'Ruang dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => Tabel::ATURAN_BERKAS], ['file.mimes' => 'Berkas harus Excel (.xlsx) atau CSV.']);
        $n = 0;
        foreach (Tabel::baca($request->file('file')) as $r) {
            $nama = trim($r['nama'] ?? $r['nama_ruang'] ?? $r['ruang'] ?? '');
            if ($nama === '') {
                continue;
            }
            $kapasitas = (int) ($r['kapasitas'] ?? 0);
            Ruang::updateOrCreate(['nama' => mb_substr($nama, 0, 100)], ['kapasitas' => $kapasitas > 0 ? $kapasitas : null]);
            $n++;
        }

        return back()->with('ok', "{$n} data ruang diimpor.");
    }

    public function template()
    {
        return Tabel::template('template-ruang.xlsx', [
            'nama' => 'Nama ruang. Ruang dengan nama yang sama akan diperbarui.',
            'kapasitas' => 'Jumlah kursi. Boleh kosong.',
        ], [
            ['Ruang 2.1', 30],
            ['Lab Microteaching', 20],
        ]);
    }
}
