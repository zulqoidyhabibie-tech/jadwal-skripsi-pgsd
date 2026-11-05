@extends('layouts.app')
@section('title', 'Ganti password')
@section('content')
<div><h1>Ganti password</h1><p class="sub">Password baru minimal 8 karakter.</p></div>
<div class="panel" style="max-width:460px">
  <form method="post" action="{{ route('password') }}">@csrf @method('put')
    <label class="f">Password lama<input type="password" name="password_lama" required autocomplete="current-password"></label>
    <label class="f">Password baru<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <label class="f">Ulangi password baru<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
    <button class="btn" style="margin-top:12px">Simpan password</button>
  </form>
</div>
@endsection
