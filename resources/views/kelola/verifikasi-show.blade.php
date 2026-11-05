@extends('layouts.app')
@section('title', 'Periksa pendaftaran')
@section('content')
@php($sk = $p->mahasiswa->skripsi)
@php($bisa = $p->status === 'diajukan')
<div>
  <a href="{{ route('kelola.verifikasi.index') }}" class="hint">&larr; Kembali</a>
  <h1>{{ $p->tahap->nama }}: {{ $p->mahasiswa->nama }}</h1>
  <p class="sub">NIM {{ $p->mahasiswa->nim }} · Pembimbing: {{ $sk?->pembimbing1?->nama ?? '–' }} dan {{ $sk?->pembimbing2?->nama ?? '–' }}</p>
</div>
@if ($sk)<div class="panel"><span class="label">Judul</span><p style="margin:4px 0 0;font-weight:600">{{ $sk->judul }}</p></div>@endif
<form method="post" action="{{ route('kelola.verifikasi.putuskan', $p) }}" class="panel">
  @csrf
  <div class="scroll"><table>
    <thead><tr><th>Syarat</th><th>Isian mahasiswa</th><th>Cek otomatis</th><th>Keputusan</th><th>Catatan</th></tr></thead>
    <tbody>
    @foreach ($cek['hasil'] as $h)
      @php($i = $h['isian'])
      <tr>
        <td><b>{{ $h['syarat']->nama }}</b></td>
        <td>@if ($h['syarat']->jenis === 'link' && $i?->nilai)<a href="{{ $i->nilai }}" target="_blank" rel="noopener">Buka berkas</a>@elseif($h['syarat']->jenis === 'centang'){{ $i?->nilai === '1' ? 'Ya' : 'Tidak' }}@else<span class="mono">{{ $i?->nilai ?? '–' }}</span>@endif</td>
        <td>{!! $h['ok'] ? '<span class="pill ok">Memenuhi</span>' : '<span class="pill bad">Tidak</span>' !!}</td>
        <td>@if ($i)
          <select name="status[{{ $i->id }}]" @disabled(! $bisa)>
            <option value="diterima" @selected($i->status !== 'ditolak' && $h['ok'])>Terima</option>
            <option value="ditolak" @selected($i->status === 'ditolak' || ! $h['ok'])>Tolak</option>
          </select>@else – @endif</td>
        <td>@if ($i)<input type="text" name="catatan[{{ $i->id }}]" value="{{ $i->catatan }}" placeholder="Opsional" @disabled(! $bisa)>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @if ($bisa)
  <div class="foot">
    <label class="f" style="flex:1">Catatan untuk mahasiswa (opsional)<input type="text" name="catatan_umum"></label>
    <button class="btn">Simpan keputusan</button>
  </div>
  <p class="hint">Bila ada satu syarat ditolak, pendaftaran dikembalikan ke mahasiswa untuk diperbaiki. Bila semua diterima, pendaftaran siap dijadwalkan.</p>
  @endif
</form>
@endsection
