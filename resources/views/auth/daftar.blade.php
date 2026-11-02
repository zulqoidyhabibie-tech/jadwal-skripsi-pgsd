@extends('layouts.tamu')
@section('title', 'Daftar akun mahasiswa')
@section('content')
<form method="post" action="{{ route('daftar') }}" class="form">
  @csrf
  <h2>Daftar akun mahasiswa</h2>
  <label class="f">NIM<input type="text" name="nim" value="{{ old('nim') }}" required inputmode="numeric"></label>
  <label class="f">Nama lengkap<input type="text" name="nama" value="{{ old('nama') }}" required></label>
  <label class="f">Email<input type="email" name="email" value="{{ old('email') }}" required></label>
  <label class="f">No. HP / WhatsApp<input type="text" name="no_hp" value="{{ old('no_hp') }}"></label>
  <label class="f">Password (minimal 8 karakter)<input type="password" name="password" required autocomplete="new-password"></label>
  <label class="f">Ulangi password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
  <button class="btn">Daftar</button>
  <p class="sub">Akun bisa dipakai setelah diaktifkan admin prodi. Sudah punya akun? <a href="{{ route('masuk') }}">Masuk</a>.</p>
</form>
@endsection
