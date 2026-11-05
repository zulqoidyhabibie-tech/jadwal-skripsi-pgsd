<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Syarat;
use App\Models\Tahap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SyaratController extends Controller
{
    public function index(Request $request)
    {
        $tahapan = Tahap::orderBy('urutan')->get();
        $tahap = $tahapan->firstWhere('kode', $request->query('tahap')) ?? $tahapan->first();

        return view('admin.syarat', ['tahapan' => $tahapan, 'tahap' => $tahap->load('syarat')]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['urutan'] = (int) Syarat::where('tahap_id', $data['tahap_id'])->max('urutan') + 1;
        Syarat::create($data);

        return back()->with('ok', 'Syarat ditambahkan. Berlaku untuk pendaftar yang belum mengajukan.');
    }

    public function update(Request $request, Syarat $syarat)
    {
        $syarat->update($this->data($request));

        return back()->with('ok', 'Syarat diperbarui.');
    }

    public function destroy(Syarat $syarat)
    {
        $syarat->delete();

        return back()->with('ok', 'Syarat dihapus.');
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'tahap_id' => ['required', 'exists:tahap,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(array_keys(Syarat::JENIS))],
            'nilai_minimal' => ['nullable', 'required_if:jenis,angka', 'numeric'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], ['nilai_minimal.required_if' => 'Isi nilai minimal untuk syarat berjenis angka.']);
        $data['wajib'] = $request->boolean('wajib');
        if ($data['jenis'] !== 'angka') {
            $data['nilai_minimal'] = null;
        }

        return $data;
    }
}
