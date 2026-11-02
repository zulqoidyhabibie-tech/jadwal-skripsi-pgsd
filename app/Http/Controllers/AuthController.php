<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function formMasuk()
    {
        return view('auth.masuk');
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['login' => 'NIM / email']);

        $kolom = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($kolom, $data['login'])->first();

        if (! $user || ! Auth::validate([$kolom => $data['login'], 'password' => $data['password']])) {
            return back()->withErrors(['login' => 'NIM/email atau password salah.'])->onlyInput('login');
        }
        if (! $user->is_active) {
            return back()->withErrors(['login' => 'Akun Anda belum diaktifkan admin prodi. Silakan tunggu atau hubungi admin.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('ingat'));
        $request->session()->regenerate();

        return redirect()->intended(route('beranda'));
    }

    public function formDaftar()
    {
        return view('auth.daftar');
    }

    public function daftar(Request $request)
    {
        $data = $request->validate([
            'nim' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ]);

        $mhs = Mahasiswa::where('nim', $data['nim'])->first();
        if (! $mhs) {
            return back()->withErrors(['nim' => 'NIM tidak ditemukan di data mahasiswa prodi. Hubungi admin agar NIM Anda ditambahkan.'])->withInput();
        }
        if ($mhs->user_id || User::where('username', $data['nim'])->exists()) {
            return back()->withErrors(['nim' => 'NIM ini sudah punya akun. Silakan masuk, atau hubungi admin bila lupa password.'])->withInput();
        }

        DB::transaction(function () use ($data, $mhs) {
            $user = User::create([
                'name' => $data['nama'],
                'username' => $data['nim'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'mahasiswa',
                'is_active' => false,
            ]);
            $mhs->update(['user_id' => $user->id, 'no_hp' => ($data['no_hp'] ?? null) ?: $mhs->no_hp]);
        });

        return redirect()->route('masuk')->with('ok', 'Pendaftaran akun berhasil. Anda bisa masuk setelah admin prodi mengaktifkan akun Anda.');
    }

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk');
    }
}
