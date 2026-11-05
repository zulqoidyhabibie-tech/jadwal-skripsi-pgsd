@extends('layouts.app')
@section('title', 'Akun pengguna')
@section('content')
<div><h1>Akun pengguna</h1><p class="sub">Mahasiswa mendaftar sendiri. Aktifkan akun mereka di sini. Akun dosen dan staf prodi dibuat di bawah. Kaprodi cukup memakai akun dosennya: buat akun dosen, lalu klik <b>Jadikan kaprodi</b> di menu Data dosen.</p></div>
<div class="tabs">
  @foreach (['menunggu' => 'Menunggu aktivasi', 'mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'kaprodi' => 'Kaprodi', 'admin' => 'Staf prodi', 'semua' => 'Semua'] as $k => $l)
    <a href="{{ route('admin.pengguna.index', ['filter' => $k]) }}" @class(['aktif' => $filter === $k])>{{ $l }}</a>
  @endforeach
</div>
<div class="panel"><div class="scroll"><table>
  <thead><tr><th>Nama</th><th>NIM / pengguna</th><th>Email</th><th>Peran</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @forelse ($pengguna as $p)
    <tr><td><b>{{ $p->name }}</b>@if($p->role === 'mahasiswa' && $p->mahasiswa && $p->mahasiswa->nama !== $p->name)<div class="hint">Di data prodi: {{ $p->mahasiswa->nama }}</div>@endif</td>
    <td class="mono">{{ $p->username }}</td><td>{{ $p->email }}</td><td>{{ \App\Models\User::ROLES[$p->role] ?? $p->role }}</td>
    <td>{!! $p->is_active ? '<span class="pill ok">Aktif</span>' : '<span class="pill wait">Belum aktif</span>' !!}</td>
    <td style="white-space:nowrap">
      @if ($p->id !== auth()->id())
      <form class="inline" method="post" action="{{ route('admin.pengguna.aktifkan', $p) }}">@csrf<button class="btn sm {{ $p->is_active ? 'ghost' : '' }}">{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
      @endif
      <form class="inline" method="post" action="{{ route('admin.pengguna.reset', $p) }}">@csrf<button class="btn sm ghost">Reset password</button></form>
    </td></tr>
  @empty
    <tr><td colspan="6" class="sub">Tidak ada akun di daftar ini.</td></tr>
  @endforelse
  </tbody>
</table></div>
{{ $pengguna->links('pagination::simple-default') }}</div>
<div class="panel" id="buat-akun">
  <h2>Buat akun dosen atau staf prodi</h2>
  <form method="post" action="{{ route('admin.pengguna.store') }}" class="form" style="margin-top:10px;max-width:none">@csrf
    <div class="row">
      <label class="f">Nama<input type="text" name="name" value="{{ old('name', $dosenDipilih?->nama) }}" required></label>
      <label class="f">Nama pengguna untuk login<input type="text" name="username" value="{{ old('username', $dosenDipilih?->nomor_identitas) }}" required placeholder="NIDN atau nama singkat"></label>
      <label class="f">Email<input type="email" name="email" value="{{ old('email') }}" required></label>
    </div>
    <div class="row">
      <label class="f">Peran<select name="role">@foreach (['dosen' => 'Dosen', 'kaprodi' => 'Dosen sekaligus kaprodi', 'admin' => 'Staf prodi (admin)'] as $k => $l)<option value="{{ $k }}" @selected(old('role') === $k)>{{ $l }}</option>@endforeach</select></label>
      <label class="f">Hubungkan ke data dosen<select name="dosen_id"><option value="">–</option>@foreach ($dosenTanpaAkun as $d)<option value="{{ $d->id }}" @selected((int) old('dosen_id', $dosenDipilih?->id) === $d->id)>{{ $d->nama }}</option>@endforeach</select></label>
      <button class="btn">Buat akun</button>
    </div>
    <p class="hint" style="margin:0">Password awal dibuat otomatis dan ditampilkan sekali setelah akun dibuat. Staf prodi bisa mengelola semua menu, termasuk akun pengguna. Akun dosen dan kaprodi sebaiknya dihubungkan ke data dosen agar bimbingannya tampil.</p>
  </form>
</div>
@endsection
