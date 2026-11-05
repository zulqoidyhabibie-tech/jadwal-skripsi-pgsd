<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodeController extends Controller
{
    public function index()
    {
        return view('admin.periode', ['periode' => Periode::latest('id')->get()]);
    }

    public function store(Request $request)
    {
        Periode::create($request->validate(['nama' => ['required', 'string', 'max:50']]));

        return back()->with('ok', 'Periode ditambahkan.');
    }

    public function aktifkan(Periode $periode)
    {
        DB::transaction(function () use ($periode) {
            Periode::query()->update(['aktif' => false]);
            $periode->update(['aktif' => true]);
        });

        return back()->with('ok', "{$periode->nama} sekarang periode aktif.");
    }
}
