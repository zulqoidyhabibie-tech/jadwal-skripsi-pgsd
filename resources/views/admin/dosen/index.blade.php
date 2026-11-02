@extends('layouts.app')
@section('title', 'Data dosen')
@section('content')
<div class="panel-head" style="margin:0"><div><h1>Data dosen</h1><p class="sub">Nomor NIDN/NUPTK boleh kosong dan bisa dilengkapi nanti.</p></div>
<a class="btn" href="{{ route('admin.dosen.create') }}">+ Tambah dosen</a></div>
<div class="panel">
  <form class="row" method="get"><input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau nomor"><button class="btn ghost">Cari</button></form>
  <div class="scroll" style="margin-top:12px"><table>
    <thead><tr><th>Nama</th><th>NIDN/NUPTK</th><th>Boleh menjadi</th><th>Kuota P1 / P2 / U1 / U2</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse ($dosen as $d)
      <tr><td><b>{{ $d->nama }}</b></td>
      <td class="mono">{{ $d->nomor_identitas ? $d->jenis_identitas.' '.$d->nomor_identitas : '–' }}</td>
      <td>{{ collect(['P1' => $d->boleh_pembimbing_1, 'P2' => $d->boleh_pembimbing_2, 'Penguji' => $d->boleh_penguji])->filter()->keys()->join(', ') ?: '–' }}</td>
      <td class="mono">{{ $d->kuota_pembimbing_1 }} / {{ $d->kuota_pembimbing_2 }} / {{ $d->kuota_penguji_1 }} / {{ $d->kuota_penguji_2 }}</td>
      <td>{!! $d->aktif ? '<span class="pill ok">Aktif</span>' : '<span class="pill off">Nonaktif</span>' !!}</td>
      <td><a class="btn sm ghost" href="{{ route('admin.dosen.edit', $d) }}">Ubah</a></td></tr>
    @empty
      <tr><td colspan="6" class="sub">Belum ada data dosen. Tambahkan satu per satu atau impor dari CSV di bawah.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $dosen->links('pagination::simple-default') }}
</div>
<div class="panel">
  <h2>Impor dari CSV</h2>
  <p class="sub">Simpan sheet sebagai CSV dengan kolom: <span class="mono">nama, jenis_identitas, nomor_identitas, boleh_p1, boleh_p2, boleh_penguji, kuota_p1, kuota_p2, kuota_u1, kuota_u2</span>. Kolom boleh_* diisi 1/0 atau ya/tidak. Dosen dengan nama yang sama akan diperbarui.</p>
  <form method="post" action="{{ route('admin.dosen.import') }}" enctype="multipart/form-data" class="row" style="margin-top:10px">@csrf
    <input type="file" name="file" accept=".csv,text/csv" required><button class="btn">Impor</button></form>
</div>
@endsection
