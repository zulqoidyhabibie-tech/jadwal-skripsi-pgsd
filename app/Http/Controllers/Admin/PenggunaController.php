<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'menunggu');
        $pengguna = User::with('mahasiswa', 'dosen')
            ->when($filter === 'menunggu', fn ($q) => $q->where('is_active', false))
            ->when(in_array($filter, array_keys(User::ROLES)), fn ($q) => $q->where('role', $filter))
            ->orderBy('is_active')->latest()->paginate(30)->withQueryString();

        return view('admin.pengguna', [
            'pengguna' => $pengguna,
            'filter' => $filter,
            'dosenTanpaAkun' => Dosen::whereNull('user_id')->where('aktif', true)->orderBy('nama')->get(),
            'dosenDipilih' => $request->query('dosen') ? Dosen::whereNull('user_id')->find($request->query('dosen')) : null,
        ]);
    }

    public function aktifkan(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('ok', $user->is_active ? "Akun {$user->name} diaktifkan." : "Akun {$user->name} dinonaktifkan.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'kaprodi', 'dosen'])],
            'dosen_id' => ['nullable', 'exists:dosen,id'],
        ]);
        $sandi = Str::password(10, symbols: false);
        if ($data['role'] === 'kaprodi') {
            // Hanya ada satu kaprodi; kaprodi sebelumnya kembali menjadi dosen.
            User::where('role', 'kaprodi')->update(['role' => 'dosen']);
        }
        $user = User::create($data + ['password' => $sandi, 'is_active' => true]);
        if (! empty($data['dosen_id'])) {
            Dosen::whereKey($data['dosen_id'])->update(['user_id' => $user->id]);
        }

        return back()->with('ok', "Akun {$user->name} dibuat. Password awal: {$sandi} (catat dan berikan ke pemilik akun).");
    }

    public function resetPassword(User $user)
    {
        $sandi = Str::password(10, symbols: false);
        $user->update(['password' => $sandi]);

        return back()->with('ok', "Password {$user->name} direset menjadi: {$sandi}");
    }
}
