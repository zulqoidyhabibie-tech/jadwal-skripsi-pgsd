@extends('layouts.app')
@section('title', 'Ringkasan')
@section('content')
<div><h1>Ringkasan</h1><p class="sub">Periode aktif: {{ \App\Models\Periode::aktif()?->nama ?? 'belum diatur' }}</p></div>
<div class="grid g3">
  <a class="panel stat" href="{{ route('kelola.judul.index') }}" style="text-decoration:none;color:inherit"><span class="label">Judul menunggu persetujuan</span><b>{{ $menungguJudul }}</b></a>
  <a class="panel stat" href="{{ route('kelola.verifikasi.index') }}" style="text-decoration:none;color:inherit"><span class="label">Pendaftaran menunggu verifikasi</span><b>{{ $menungguVerifikasi }}</b></a>
  @if (auth()->user()->hasRole('admin'))
  <a class="panel stat" href="{{ route('admin.pengguna.index') }}" style="text-decoration:none;color:inherit"><span class="label">Akun menunggu aktivasi</span><b>{{ $akunBaru }}</b></a>
  @else
  <div class="panel stat"><span class="label">Skripsi berjalan</span><b>{{ $skripsiAktif }}</b></div>
  @endif
</div>
<div class="grid g3">
  <div class="panel stat"><span class="label">Mahasiswa terdata</span><b>{{ $jumlahMahasiswa }}</b></div>
  <div class="panel stat"><span class="label">Dosen aktif</span><b>{{ $jumlahDosen }}</b></div>
  <div class="panel stat"><span class="label">Skripsi berjalan</span><b>{{ $skripsiAktif }}</b></div>
</div>
@endsection
