@extends('layouts.app')
@section('title', 'Data dosen')
@section('content')
<div class="panel-head" style="margin:0"><div><h1>Data dosen</h1><p class="sub">Nomor NIDN/NUPTK boleh kosong dan bisa dilengkapi nanti.</p></div>
<a class="btn" href="{{ route('admin.dosen.create') }}">+ Tambah dosen</a></div>
<div class="panel">
  <form class="row" method="get"><input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau nomor"><button class="btn ghost">Cari</button></form>
  <div class="scroll" style="margin-top:12px"><table>
    <thead><tr><th>Nama</th><th>NIDN/NUPTK</th><th>Boleh menjadi</th><th>Kuota P1 / P2 / U1 / U2</th><th>Status</th><th>Akun</th><th></th></tr></thead>
    <tbody>
    @forelse ($dosen as $d)
      <tr><td><b>{{ $d->nama }}</b></td>
      <td class="mono">{{ $d->nomor_identitas ? $d->jenis_identitas.' '.$d->nomor_identitas : '–' }}</td>
      <td>{{ collect(['P1' => $d->boleh_pembimbing_1, 'P2' => $d->boleh_pembimbing_2, 'Penguji' => $d->boleh_penguji])->filter()->keys()->join(', ') ?: '–' }}</td>
      <td class="mono">{{ $d->kuota_pembimbing_1 }} / {{ $d->kuota_pembimbing_2 }} / {{ $d->kuota_penguji_1 }} / {{ $d->kuota_penguji_2 }}</td>
      <td>{!! $d->aktif ? '<span class="pill ok">Aktif</span>' : '<span class="pill off">Nonaktif</span>' !!}</td>
      <td style="white-space:nowrap">
        @if ($d->user)
          <span class="mono" style="margin-right:6px">{{ $d->user->username }}</span>
          @if ($d->user->role === 'kaprodi')
            <span class="pill ok">Kaprodi</span>
            <form class="inline" method="post" action="{{ route('admin.dosen.lepas-kaprodi', $d) }}">@csrf @method('delete')<button class="btn sm ghost">Lepas kaprodi</button></form>
          @elseif ($d->user->role === 'dosen')
            <form class="inline" method="post" action="{{ route('admin.dosen.kaprodi', $d) }}">@csrf<button class="btn sm ghost">Jadikan kaprodi</button></form>
          @endif
        @else
          <a class="btn sm ghost" href="{{ route('admin.pengguna.index', ['filter' => 'dosen', 'dosen' => $d->id]) }}#buat-akun">Buat akun</a>
        @endif
      </td>
      <td><a class="btn sm ghost" href="{{ route('admin.dosen.edit', $d) }}">Ubah</a></td></tr>
    @empty
      <tr><td colspan="7" class="sub">Belum ada data dosen. Tambahkan satu per satu atau impor dari Excel di bawah.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  {{ $dosen->links('pagination::simple-default') }}
</div>
<div class="panel">
  <h2>Impor dari Excel atau CSV</h2>
  <p class="sub">Unduh template, isi sheet "Data", lalu unggah berkas .xlsx-nya. Petunjuk tiap kolom ada di sheet "Petunjuk". Format CSV lama tetap diterima. Dosen dengan nama yang sama akan diperbarui.</p>
  <form method="post" action="{{ route('admin.dosen.import') }}" enctype="multipart/form-data" class="row" style="margin-top:10px">@csrf
    <input type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required><button class="btn">Impor</button>
    <a class="btn ghost" href="{{ route('admin.dosen.template') }}">Unduh template Excel</a></form>
</div>
@endsection
