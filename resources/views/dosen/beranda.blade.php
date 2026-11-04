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
<div class="panel">
  <div class="panel-head"><h2>Jadwal ujian saya</h2><span class="hint">Seminar dan sidang yang sudah diterbitkan</span></div>
  <div class="scroll"><table>
    <thead><tr><th>Waktu</th><th>Ruang</th><th>Mahasiswa</th><th>Tahap</th><th>Peran saya</th></tr></thead>
    <tbody>
    @forelse ($jadwal as $j)
      <tr><td class="mono">{{ $j->sesi->tanggalLengkap() }}<br>{{ $j->sesi->waktu() }}</td><td>{{ $j->sesi->ruang->nama }}<div class="mono">{{ $j->sesi->kode }}</div></td>
      <td>{{ $j->pendaftaran->mahasiswa->nama }}</td><td>{{ $j->sesi->tahap->nama }}</td>
      <td><span class="pill {{ str_starts_with($j->peranSaya, 'Penguji') ? 'wait' : 'ok' }}">{{ $j->peranSaya }}</span></td></tr>
    @empty
      <tr><td colspan="5" class="sub">Belum ada jadwal.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@endsection
