@extends('layouts.app')
@section('title', $dosen->exists ? 'Ubah dosen' : 'Tambah dosen')
@section('content')
<div><a href="{{ route('admin.dosen.index') }}" class="hint">&larr; Data dosen</a><h1>{{ $dosen->exists ? 'Ubah dosen' : 'Tambah dosen' }}</h1></div>
<form method="post" action="{{ $dosen->exists ? route('admin.dosen.update', $dosen) : route('admin.dosen.store') }}" class="panel form">
  @csrf @if ($dosen->exists) @method('put') @endif
  <label class="f">Nama lengkap dengan gelar<input type="text" name="nama" value="{{ old('nama', $dosen->nama) }}" required></label>
  <div class="row">
    <label class="f" style="flex:0 0 140px">Jenis nomor<select name="jenis_identitas"><option value="">–</option>@foreach (['NIDN', 'NUPTK'] as $j)<option @selected(old('jenis_identitas', $dosen->jenis_identitas) === $j)>{{ $j }}</option>@endforeach</select></label>
    <label class="f">Nomor (boleh kosong)<input type="text" name="nomor_identitas" value="{{ old('nomor_identitas', $dosen->nomor_identitas) }}"></label>
  </div>
  <label class="f">Bidang keahlian<input type="text" name="bidang_keahlian" value="{{ old('bidang_keahlian', $dosen->bidang_keahlian) }}" placeholder="Contoh: Pendidikan Matematika SD"></label>
  <fieldset style="border:1px solid var(--line);border-radius:8px;padding:12px;display:grid;gap:6px">
    <legend class="label">Boleh menjadi</legend>
    <label class="chk"><input type="checkbox" name="boleh_pembimbing_1" value="1" @checked(old('boleh_pembimbing_1', $dosen->boleh_pembimbing_1))> Pembimbing 1</label>
    <label class="chk"><input type="checkbox" name="boleh_pembimbing_2" value="1" @checked(old('boleh_pembimbing_2', $dosen->boleh_pembimbing_2))> Pembimbing 2</label>
    <label class="chk"><input type="checkbox" name="boleh_penguji" value="1" @checked(old('boleh_penguji', $dosen->boleh_penguji))> Penguji</label>
  </fieldset>
  <div class="row">
    @foreach (['kuota_pembimbing_1' => 'Kuota P1', 'kuota_pembimbing_2' => 'Kuota P2', 'kuota_penguji_1' => 'Kuota U1', 'kuota_penguji_2' => 'Kuota U2'] as $f => $l)
      <label class="f">{{ $l }}<input type="number" min="0" max="99" name="{{ $f }}" value="{{ old($f, $dosen->$f ?? 0) }}" required></label>
    @endforeach
  </div>
  <p class="hint" style="margin:0">Kuota 0 berarti tidak dibatasi.</p>
  <label class="chk"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $dosen->aktif))> Dosen aktif</label>
  <button class="btn" style="width:fit-content">Simpan</button>
</form>
@endsection
