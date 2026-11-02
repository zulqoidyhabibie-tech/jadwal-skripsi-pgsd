<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Beranda') · Siskripsi PGSD</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php($u = auth()->user())
<div class="app">
  <aside>
    <a class="brand" href="{{ route('beranda') }}">Siskripsi PGSD<small>Universitas Muhammadiyah Muara Bungo</small></a>
    <nav class="nav">
      @if ($u->hasRole('mahasiswa'))
        <a href="{{ route('mhs.beranda') }}" @class(['aktif' => request()->routeIs('mhs.*')])>Skripsi saya</a>
      @endif
      @if ($u->hasRole('dosen'))
        <a href="{{ route('dosen.beranda') }}" @class(['aktif' => request()->routeIs('dosen.*')])>Bimbingan saya</a>
      @endif
      @if ($u->bisaKelolaSkripsi())
        <a href="{{ route('beranda') }}" @class(['aktif' => request()->routeIs('beranda')])>Ringkasan</a>
        <span class="label">Kaprodi</span>
        <a href="{{ route('kelola.judul.index') }}" @class(['aktif' => request()->routeIs('kelola.judul.*')])>Persetujuan judul</a>
        <a href="{{ route('kelola.verifikasi.index') }}" @class(['aktif' => request()->routeIs('kelola.verifikasi.*')])>Verifikasi pendaftaran</a>
        <a href="{{ route('kelola.rekap') }}" @class(['aktif' => request()->routeIs('kelola.rekap')])>Rekap beban dosen</a>
      @endif
      @if ($u->hasRole('admin'))
        <span class="label">Admin</span>
        <a href="{{ route('admin.pengguna.index') }}" @class(['aktif' => request()->routeIs('admin.pengguna.*')])>Akun pengguna</a>
        <a href="{{ route('admin.mahasiswa.index') }}" @class(['aktif' => request()->routeIs('admin.mahasiswa.*')])>Data mahasiswa</a>
        <a href="{{ route('admin.dosen.index') }}" @class(['aktif' => request()->routeIs('admin.dosen.*')])>Data dosen</a>
        <a href="{{ route('admin.ruang.index') }}" @class(['aktif' => request()->routeIs('admin.ruang.*')])>Ruang</a>
        <a href="{{ route('admin.syarat.index') }}" @class(['aktif' => request()->routeIs('admin.syarat.*')])>Atur syarat</a>
        <a href="{{ route('admin.periode.index') }}" @class(['aktif' => request()->routeIs('admin.periode.*')])>Periode</a>
      @endif
    </nav>
    <div class="me">
      <b>{{ $u->name }}</b>{{ \App\Models\User::ROLES[$u->role] ?? $u->role }} · {{ $u->username }}
      <form method="post" action="{{ route('keluar') }}" style="margin-top:8px">@csrf<button class="btn ghost sm">Keluar</button></form>
    </div>
  </aside>
  <main>
    @include('partials.flash')
    @yield('content')
  </main>
</div>
</body>
</html>
