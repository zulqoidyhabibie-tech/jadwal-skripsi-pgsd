@extends('layouts.tamu')
@section('title', 'Masuk')
@section('content')
<form method="post" action="{{ route('masuk') }}" class="form">
  @csrf
  <label class="f">NIM, nama pengguna, atau email<input type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"></label>
  <label class="f">Password<input type="password" name="password" required autocomplete="current-password"></label>
  <label class="chk"><input type="checkbox" name="ingat" value="1"> Ingat saya</label>
  <button class="btn">Masuk</button>
  <p class="sub">Mahasiswa belum punya akun? <a href="{{ route('daftar') }}">Daftar di sini</a>.</p>
</form>
@endsection
