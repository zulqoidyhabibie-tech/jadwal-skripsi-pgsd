@extends('layouts.app')
@section('title', 'Verifikasi pendaftaran')
@section('content')
<div><h1>Verifikasi pendaftaran</h1><p class="sub">Seminar proposal dan sidang skripsi.</p></div>
<div class="tabs">
  @foreach (['diajukan' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Dikembalikan', 'semua' => 'Semua'] as $k => $l)
    <a href="{{ route('kelola.verifikasi.index', ['status' => $k]) }}" @class(['aktif' => $status === $k])>{{ $l }}</a>
  @endforeach
</div>
<div class="panel"><div class="scroll"><table>
  <thead><tr><th>Mahasiswa</th><th>Tahap</th><th>Diajukan</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @forelse ($daftar as $p)
    <tr><td><b>{{ $p->mahasiswa->nama }}</b><div class="mono">{{ $p->mahasiswa->nim }}</div></td>
    <td>{{ $p->tahap->nama }}</td><td class="mono">{{ $p->diajukan_pada?->format('d-m-Y H.i') ?? '–' }}</td>
    <td><span class="pill {{ $p->status === 'diajukan' ? 'wait' : ($p->status === 'ditolak' ? 'bad' : 'ok') }}">{{ $p->statusLabel() }}</span></td>
    <td><a class="btn sm {{ $p->status === 'diajukan' ? '' : 'ghost' }}" href="{{ route('kelola.verifikasi.show', $p) }}">{{ $p->status === 'diajukan' ? 'Periksa' : 'Lihat' }}</a></td></tr>
  @empty
    <tr><td colspan="5" class="sub">Tidak ada data.</td></tr>
  @endforelse
  </tbody>
</table></div></div>
@endsection
