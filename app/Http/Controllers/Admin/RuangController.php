<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ruang;
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
}
