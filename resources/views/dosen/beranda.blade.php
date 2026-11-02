@extends('layouts.app')
@section('title', 'Bimbingan saya')
@section('content')
<div><h1>Bimbingan saya</h1><p class="sub">{{ $dosen?->nama ?? 'Akun ini belum terhubung ke data dosen. Hubungi admin.' }}</p></div>
<div class="panel">
  <div class="panel-head"><h2>Mahasiswa bimbingan</h2><span class="pill off">{{ $bimbingan->count() }} mahasiswa</span></div>
  <div class="scroll"><table>
    <thead><tr><th>Mahasiswa</th><th>Judul</th><th>Peran saya</th></tr></thead>
    <tbody>
    @forelse ($bimbingan as $s)
      <tr><td><b>{{ $s->mahasiswa->nama }}</b><div class="mono">{{ $s->mahasiswa->nim }}</div></td><td>{{ $s->judul }}</td>
      <td><span class="pill ok">{{ $s->pembimbing_1_id === $dosen->id ? 'Pembimbing 1' : 'Pembimbing 2' }}</span></td></tr>
    @empty
      <tr><td colspan="3" class="sub">Belum ada mahasiswa bimbingan.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
<p class="sub">Jadwal menguji akan tampil di sini setelah fitur penjadwalan selesai.</p>
@endsection
