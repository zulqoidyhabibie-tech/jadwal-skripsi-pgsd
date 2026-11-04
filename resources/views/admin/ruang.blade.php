@extends('layouts.app')
@section('title', 'Ruang')
@section('content')
<div><h1>Ruang ujian</h1></div>
<div class="panel">
  <div class="scroll"><table>
    <thead><tr><th>Nama ruang</th><th>Kapasitas</th><th></th></tr></thead>
    <tbody>
    @forelse ($ruang as $r)
      <tr><td colspan="2">
        <form method="post" action="{{ route('admin.ruang.update', $r) }}" class="row">@csrf @method('put')
          <input type="text" name="nama" value="{{ $r->nama }}" required aria-label="Nama ruang">
          <input type="number" name="kapasitas" value="{{ $r->kapasitas }}" min="1" aria-label="Kapasitas" style="max-width:120px">
          <button class="btn sm ghost">Simpan</button></form></td>
      <td><form method="post" action="{{ route('admin.ruang.destroy', $r) }}">@csrf @method('delete')<button class="btn sm bad">Hapus</button></form></td></tr>
    @empty
      <tr><td colspan="3" class="sub">Belum ada ruang.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <form method="post" action="{{ route('admin.ruang.store') }}" class="row" style="margin-top:14px">@csrf
    <label class="f">Ruang baru<input type="text" name="nama" required placeholder="Ruang 2.3"></label>
    <label class="f" style="max-width:140px">Kapasitas<input type="number" name="kapasitas" min="1"></label>
    <button class="btn">Tambah</button></form>
</div>
<div class="panel">
  <h2>Impor dari Excel atau CSV</h2>
  <p class="sub">Kolom: nama dan kapasitas. Ruang dengan nama yang sama akan diperbarui.</p>
  <form method="post" action="{{ route('admin.ruang.import') }}" enctype="multipart/form-data" class="row" style="margin-top:10px">@csrf
    <input type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required><button class="btn">Impor</button>
    <a class="btn ghost" href="{{ route('admin.ruang.template') }}">Unduh template Excel</a></form>
</div>
@endsection
