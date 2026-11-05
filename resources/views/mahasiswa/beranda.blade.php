@extends('layouts.app')
@section('title', 'Skripsi saya')
@section('content')
@php($sk = $mhs->skripsi)
<div>
  <h1>Halo, {{ $mhs->nama }}</h1>
  <p class="sub">NIM {{ $mhs->nim }}
    @if ($sk && $sk->status === 'disetujui') · Pembimbing: {{ $sk->pembimbing1?->nama }} dan {{ $sk->pembimbing2?->nama }}@endif
  </p>
</div>
@if ($sk)
<div class="panel"><span class="label">Judul skripsi</span><p style="margin:4px 0 0;font-weight:600">{{ $sk->judul }}</p>
  @if ($sk->status === 'revisi' && $sk->catatan)<p class="sub">Catatan kaprodi: {{ $sk->catatan }}</p>@endif
</div>
@endif
<div class="grid g3">
@foreach ($tahapan as $i => $t)
  @php($p = $t['pendaftaran'])
  @php($sekarang = $t['terbuka'] && (! $p || $p->status !== 'lulus'))
  <div @class(['stage', 'now' => $sekarang])>
    <span class="t">{{ $i + 1 }}. {{ $t['tahap']->nama }}</span>
    @if (! $t['terbuka'])
      <span class="pill off">Terkunci</span><span class="hint">Terbuka setelah tahap sebelumnya lulus.</span>
    @elseif (! $p)
      <span class="pill off">Belum mendaftar</span>
      <a class="btn sm" href="{{ route('mhs.tahap', $t['tahap']->kode) }}">Isi syarat</a>
    @else
      <span @class(['pill', 'ok' => in_array($p->status, ['lulus', 'terverifikasi', 'dijadwalkan']), 'wait' => $p->status === 'diajukan', 'bad' => in_array($p->status, ['ditolak', 'mengulang']), 'off' => $p->status === 'draf'])>{{ $p->statusLabel() }}</span>
      @if ($p->status === 'ditolak' && $p->catatan)<span class="hint">Catatan: {{ $p->catatan }}</span>@endif
      @if ($p->status === 'mengulang')<span class="hint">Anda bisa mendaftar ulang tahap ini.</span>@endif
      @if ($p->jadwal && $p->jadwal->sesi->terbit)
        @php($js = $p->jadwal->sesi)
        <span class="hint"><b>{{ $js->tanggalLengkap() }}</b>, {{ $js->waktu() }}, {{ $js->ruang->nama }}<br>Penguji: {{ $p->jadwal->penguji1->nama }} dan {{ $p->jadwal->penguji2->nama }}</span>
        @if ($p->jadwal->hasil)<span class="pill {{ $p->jadwal->hasil === 'mengulang' ? 'bad' : 'ok' }}">{{ \App\Models\JadwalUjian::HASIL[$p->jadwal->hasil] }}</span>@endif
        <a class="btn sm ghost" href="{{ route('jadwal.kartu', $p->jadwal) }}" target="_blank">Kartu ujian</a>
      @elseif ($p->status === 'dijadwalkan')
        <span class="hint">Jadwal sedang disusun prodi.</span>
      @endif
      <a class="btn sm {{ $p->bisaDiubah() || $p->status === 'mengulang' ? '' : 'ghost' }}" href="{{ route('mhs.tahap', $t['tahap']->kode) }}">{{ $p->bisaDiubah() || $p->status === 'mengulang' ? 'Lanjutkan isi syarat' : 'Lihat isian' }}</a>
    @endif
  </div>
@endforeach
</div>
@endsection
