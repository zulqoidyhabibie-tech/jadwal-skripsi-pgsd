<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Support\Csv;
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
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $n = 0;
        foreach (Csv::baca($request->file('file')->getRealPath()) as $r) {
            $nim = trim($r['nim'] ?? '');
            $nama = trim($r['nama'] ?? '');
            if ($nim === '' || $nama === '') {
                continue;
            }
            Mahasiswa::updateOrCreate(['nim' => $nim], array_filter([
                'nama' => $nama,
                'angkatan' => trim($r['angkatan'] ?? '') ?: null,
                'no_hp' => trim($r['no_hp'] ?? '') ?: null,
            ], fn ($v) => $v !== null));
            $n++;
        }

        return back()->with('ok', "{$n} data mahasiswa diimpor.");
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
}
