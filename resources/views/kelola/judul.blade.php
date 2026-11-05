@extends('layouts.app')
@section('title', 'Persetujuan judul')
@section('content')
<div><h1>Persetujuan judul dan pembimbing</h1>
<p class="sub">Sistem menyarankan dosen yang bebannya paling ringan dibanding kuotanya. Anda bisa menggantinya sebelum menyetujui.</p></div>
@forelse ($daftar as $p)
  @php($sk = $p->mahasiswa->skripsi)
  @php($c = $cek[$p->id])
  <div class="panel">
    <div class="panel-head">
      <div><h2>{{ $p->mahasiswa->nama }} <span class="mono" style="color:var(--muted)">{{ $p->mahasiswa->nim }}</span></h2>
      <p class="sub">Diajukan {{ $p->diajukan_pada?->translatedFormat('d M Y, H.i') }}</p></div>
      <span @class(['pill', 'ok' => $c['lengkap'], 'bad' => ! $c['lengkap']])>{{ $c['lengkap'] ? 'Syarat lengkap' : 'Syarat belum lengkap' }}</span>
    </div>
    <p style="font-weight:600;margin:0 0 10px">{{ $sk?->judul }}</p>
    <details><summary>Lihat isian syarat</summary>
      <table style="margin-top:8px"><tbody>
      @foreach ($c['hasil'] as $h)
        <tr><td>{{ $h['syarat']->nama }}</td>
        <td>@if($h['syarat']->jenis === 'link' && $h['isian']?->nilai)<a href="{{ $h['isian']->nilai }}" target="_blank" rel="noopener">Buka berkas</a>@else{{ $h['isian']?->nilai === '1' && $h['syarat']->jenis === 'centang' ? 'Ya' : ($h['isian']?->nilai ?? '–') }}@endif</td>
        <td>{!! $h['ok'] ? '<span class="pill ok">OK</span>' : '<span class="pill bad">Tidak</span>' !!}</td></tr>
      @endforeach
      </tbody></table>
    </details>
    <form method="post" action="{{ route('kelola.judul.setujui', $p) }}" class="row" style="margin-top:14px">
      @csrf
      <label class="f">Pembimbing 1
        <select name="pembimbing_1_id" required>
          @foreach ($kandidat1 as $k)
            <option value="{{ $k['dosen']->id }}" @selected($saran[0]?->id === $k['dosen']->id)>{{ $k['dosen']->nama }} — {{ $k['beban'] }}{{ $k['kuota'] ? '/'.$k['kuota'] : '' }}{{ $k['penuh'] ? ' (penuh)' : '' }}</option>
          @endforeach
        </select>
      </label>
      <label class="f">Pembimbing 2
        <select name="pembimbing_2_id" required>
          @foreach ($kandidat2 as $k)
            <option value="{{ $k['dosen']->id }}" @selected($saran[1]?->id === $k['dosen']->id)>{{ $k['dosen']->nama }} — {{ $k['beban'] }}{{ $k['kuota'] ? '/'.$k['kuota'] : '' }}{{ $k['penuh'] ? ' (penuh)' : '' }}</option>
          @endforeach
        </select>
      </label>
      <button class="btn">Setujui judul</button>
    </form>
    <details style="margin-top:10px"><summary>Kembalikan untuk revisi</summary>
      <form method="post" action="{{ route('kelola.judul.revisi', $p) }}" class="form" style="margin-top:8px">
        @csrf
        <textarea name="catatan" placeholder="Tulis alasan dan saran perbaikan judul" required></textarea>
        <button class="btn bad" style="width:fit-content">Kembalikan ke mahasiswa</button>
      </form>
    </details>
  </div>
@empty
  <div class="panel"><p class="sub">Tidak ada judul yang menunggu persetujuan.</p></div>
@endforelse
<p class="hint">Angka di samping nama dosen: jumlah bimbingan aktif / kuota. Tanpa angka kuota berarti tidak dibatasi.</p>
@endsection
