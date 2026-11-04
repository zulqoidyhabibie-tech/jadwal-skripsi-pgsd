@extends('layouts.app')
@section('title', 'Data mahasiswa')
@section('content')
<div class="panel-head" style="margin:0"><div><h1>Data mahasiswa</h1><p class="sub">Mahasiswa hanya bisa membuat akun bila NIM-nya ada di daftar ini.</p></div>
<a class="btn" href="{{ route('admin.mahasiswa.create') }}">+ Tambah mahasiswa</a></div>
<div class="panel">
  <form class="row" method="get"><input type="text" name="q" value="{{ $q }}" placeholder="Cari NIM atau nama"><button class="btn ghost">Cari</button></form>
  <div class="scroll" style="margin-top:12px"><table>
    <thead><tr><th>NIM</th><th>Nama</th><th>Angkatan</th><th>Akun</th><th>Judul</th><th></th></tr></thead>
    <tbody>
    @forelse ($mahasiswa as $m)
      <tr><td class="mono">{{ $m->nim }}</td><td><b>{{ $m->nama }}</b></td><td class="mono">{{ $m->angkatan ?? '–' }}</td>
      <td>@if (! $m->user)<span class="pill off">Belum daftar</span>@elseif(! $m->user->is_active)<span class="pill wait">Menunggu aktivasi</span>@else<span class="pill ok">Aktif</span>@endif</td>
      <td style="max-width:320px">{{ $m->skripsi?->judul ?? '–' }}</td>
      <td><a class="btn sm ghost" href="{{ route('admin.mahasiswa.edit', $m) }}">Ubah</a></td></tr>
    @empty
      <tr><td colspan="6" class="sub">Belum ada data. Impor dari Excel di bawah.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $mahasiswa->links('pagination::simple-default') }}
</div>
<div class="panel">
  <h2>Impor dari Excel atau CSV</h2>
  <p class="sub">Unduh template, isi sheet "Data", lalu unggah berkas .xlsx-nya. Tab DATA MHS dari Sheet lama juga bisa langsung diunggah (unduh sebagai .xlsx atau .csv). Mahasiswa yang sudah punya judul dan kedua pembimbing akan tercatat judulnya disetujui. NIM yang sudah ada akan diperbarui.</p>
  <form method="post" action="{{ route('admin.mahasiswa.import') }}" enctype="multipart/form-data" class="row" style="margin-top:10px">@csrf
    <input type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required><button class="btn">Impor</button>
    <a class="btn ghost" href="{{ route('admin.mahasiswa.template') }}">Unduh template Excel</a></form>
</div>
@endsection
