@extends('layouts.app')
@section('title', $mahasiswa->exists ? 'Ubah mahasiswa' : 'Tambah mahasiswa')
@section('content')
<div><a href="{{ route('admin.mahasiswa.index') }}" class="hint">&larr; Data mahasiswa</a><h1>{{ $mahasiswa->exists ? 'Ubah mahasiswa' : 'Tambah mahasiswa' }}</h1></div>
<form method="post" action="{{ $mahasiswa->exists ? route('admin.mahasiswa.update', $mahasiswa) : route('admin.mahasiswa.store') }}" class="panel form">
  @csrf @if ($mahasiswa->exists) @method('put') @endif
  <label class="f">NIM<input type="text" name="nim" value="{{ old('nim', $mahasiswa->nim) }}" required></label>
  <label class="f">Nama lengkap<input type="text" name="nama" value="{{ old('nama', $mahasiswa->nama) }}" required></label>
  <div class="row">
    <label class="f">Angkatan<input type="text" name="angkatan" value="{{ old('angkatan', $mahasiswa->angkatan) }}" placeholder="2022"></label>
    <label class="f">No. HP<input type="text" name="no_hp" value="{{ old('no_hp', $mahasiswa->no_hp) }}"></label>
  </div>
  <button class="btn" style="width:fit-content">Simpan</button>
</form>
@endsection
