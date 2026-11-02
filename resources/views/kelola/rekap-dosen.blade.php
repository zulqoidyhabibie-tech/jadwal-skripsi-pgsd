@extends('layouts.app')
@section('title', 'Rekap beban dosen')
@section('content')
<div><h1>Rekap beban dosen</h1><p class="sub">Bimbingan aktif dibanding kuota tiap dosen. Rekap penguji akan muncul setelah fitur penjadwalan selesai.</p></div>
<div class="panel"><div class="scroll"><table>
  <thead><tr><th>Dosen</th><th>Pembimbing 1</th><th>Pembimbing 2</th><th>Kuota penguji 1 / 2</th></tr></thead>
  <tbody>
  @foreach ($dosen as $d)
    @php($b1 = $beban[$d->id]['p1'] ?? 0)
    @php($b2 = $beban[$d->id]['p2'] ?? 0)
    <tr><td><b>{{ $d->nama }}</b><div class="hint">{{ $d->jenis_identitas }} {{ $d->nomor_identitas ?? 'nomor belum diisi' }}</div></td>
    @foreach ([[$b1, $d->kuota_pembimbing_1, $d->boleh_pembimbing_1], [$b2, $d->kuota_pembimbing_2, $d->boleh_pembimbing_2]] as [$b, $k, $boleh])
      <td style="min-width:150px">@if ($boleh)
        <div class="mono">{{ $b }}{{ $k ? ' / '.$k : '' }}</div>
        @if ($k)<div style="background:var(--bg);border-radius:4px;margin-top:4px"><div class="bar {{ $b >= $k ? 'full' : '' }}" style="width:{{ min(100, $b / $k * 100) }}%"></div></div>@endif
      @else <span class="hint">tidak bertugas</span> @endif</td>
    @endforeach
    <td class="mono">{{ $d->kuota_penguji_1 }} / {{ $d->kuota_penguji_2 }}</td></tr>
  @endforeach
  </tbody>
</table></div></div>
@endsection
