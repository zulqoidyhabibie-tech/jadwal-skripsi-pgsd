@extends('layouts.app')
@section('title', 'Atur syarat')
@section('content')
<div><h1>Atur syarat</h1><p class="sub">Syarat yang wajib harus terpenuhi sebelum tombol Daftar aktif untuk mahasiswa.</p></div>
<div class="tabs">@foreach ($tahapan as $t)<a href="{{ route('admin.syarat.index', ['tahap' => $t->kode]) }}" @class(['aktif' => $t->id === $tahap->id])>{{ $t->nama }}</a>@endforeach</div>
<div class="panel">
  <div class="panel-head"><h2>Syarat {{ strtolower($tahap->nama) }}</h2></div>
  @forelse ($tahap->syarat as $s)
    <form method="post" action="{{ route('admin.syarat.update', $s) }}" class="row" style="padding:10px 0;border-top:1px solid var(--line)">
      @csrf @method('put')<input type="hidden" name="tahap_id" value="{{ $tahap->id }}">
      <label class="f" style="flex:2">Nama syarat<input type="text" name="nama" value="{{ $s->nama }}" required></label>
      <label class="f">Jenis<select name="jenis">@foreach (\App\Models\Syarat::JENIS as $k => $l)<option value="{{ $k }}" @selected($s->jenis === $k)>{{ $l }}</option>@endforeach</select></label>
      <label class="f" style="max-width:120px">Nilai min.<input type="text" name="nilai_minimal" value="{{ $s->nilai_minimal }}" inputmode="decimal"></label>
      <label class="f" style="flex:2">Keterangan<input type="text" name="keterangan" value="{{ $s->keterangan }}"></label>
      <label class="chk"><input type="checkbox" name="wajib" value="1" @checked($s->wajib)> Wajib</label>
      <button class="btn sm ghost">Simpan</button>
      <button class="btn sm bad" form="hapus-{{ $s->id }}">Hapus</button>
    </form>
    <form id="hapus-{{ $s->id }}" method="post" action="{{ route('admin.syarat.destroy', $s) }}">@csrf @method('delete')</form>
  @empty
    <p class="sub">Belum ada syarat.</p>
  @endforelse
</div>
<div class="panel">
  <h2>Tambah syarat</h2>
  <form method="post" action="{{ route('admin.syarat.store') }}" class="row" style="margin-top:10px">@csrf
    <input type="hidden" name="tahap_id" value="{{ $tahap->id }}">
    <label class="f" style="flex:2">Nama syarat<input type="text" name="nama" required placeholder="Contoh: Surat bebas pustaka"></label>
    <label class="f">Jenis<select name="jenis">@foreach (\App\Models\Syarat::JENIS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
    <label class="f" style="max-width:120px">Nilai min.<input type="text" name="nilai_minimal" inputmode="decimal" placeholder="Untuk angka"></label>
    <label class="f" style="flex:2">Keterangan<input type="text" name="keterangan"></label>
    <label class="chk"><input type="checkbox" name="wajib" value="1" checked> Wajib</label>
    <button class="btn">Tambah</button>
  </form>
</div>
@endsection
