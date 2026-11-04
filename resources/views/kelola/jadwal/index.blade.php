@extends('layouts.app')
@section('title', 'Jadwal '.strtolower($tahap->nama))
@section('content')
@php($admin = auth()->user()->hasRole('admin'))
<div class="panel-head" style="margin:0">
  <div><h1>Jadwal {{ strtolower($tahap->nama) }}</h1>
  <p class="sub">Satu sesi berisi beberapa mahasiswa dengan ruang dan jam yang sama, seperti di Sheet lama. {{ $siap }} mahasiswa terverifikasi menunggu dijadwalkan.</p></div>
  <a class="btn ghost" href="{{ route('kelola.jadwal.ekspor', ['tahap' => $tahap->kode]) }}">Ekspor ke Excel (CSV)</a>
</div>
<div class="tabs">@foreach ($tahapan as $t)<a href="{{ route('kelola.jadwal.index', ['tahap' => $t->kode]) }}" @class(['aktif' => $t->id === $tahap->id])>{{ $t->nama }}</a>@endforeach</div>

@foreach ($bentrok->filter() as $id => $pesan)
  <div class="flash err">Sesi {{ $sesi->firstWhere('id', $id)->kode }} bentrok: {{ implode(' ', $pesan) }}</div>
@endforeach

<div class="panel"><div class="scroll"><table>
  <thead><tr><th>Kode</th><th>Hari, tanggal</th><th>Waktu</th><th>Ruang</th><th>Mahasiswa</th><th>Dospem 1</th><th>Dospem 2</th><th>Dospeng 1</th><th>Dospeng 2</th><th></th></tr></thead>
  <tbody>
  @forelse ($sesi as $s)
    @php($n = max(1, $s->jadwal->count()))
    @foreach ($s->jadwal->isEmpty() ? [null] : $s->jadwal as $i => $j)
      <tr @if($bentrok[$s->id]) style="background:var(--bad-soft)" @endif>
        @if ($i === 0)
          <td rowspan="{{ $n }}" class="mono"><b>{{ $s->kode }}</b><div>{!! $s->terbit ? '<span class="pill ok">Terbit</span>' : '<span class="pill off">Draf</span>' !!}</div></td>
          <td rowspan="{{ $n }}">{{ $s->hari() }}<div class="mono">{{ $s->tanggal->format('d-m-Y') }}</div></td>
          <td rowspan="{{ $n }}" class="mono">{{ $s->waktu() }}</td>
          <td rowspan="{{ $n }}">{{ $s->ruang->nama }}</td>
        @endif
        @if ($j)
          @php($sk = $j->pendaftaran->mahasiswa->skripsi)
          <td><b>{{ $j->pendaftaran->mahasiswa->nama }}</b>@if($j->hasil)<div><span class="pill {{ $j->hasil === 'mengulang' ? 'bad' : 'ok' }}">{{ \App\Models\JadwalUjian::HASIL[$j->hasil] }}</span></div>@endif</td>
          <td>{{ $sk?->pembimbing1?->nama }}</td><td>{{ $sk?->pembimbing2?->nama }}</td>
          <td>{{ $j->penguji1->nama }}</td><td>{{ $j->penguji2->nama }}</td>
        @else
          <td colspan="5" class="sub">Belum ada mahasiswa ({{ $s->kapasitas }} slot kosong)</td>
        @endif
        @if ($i === 0)
          <td rowspan="{{ $n }}"><a class="btn sm" href="{{ route('kelola.jadwal.sesi', $s) }}">{{ $admin ? 'Atur' : 'Lihat' }}</a></td>
        @endif
      </tr>
    @endforeach
  @empty
    <tr><td colspan="10" class="sub">Belum ada sesi untuk tahap ini.</td></tr>
  @endforelse
  </tbody>
</table></div></div>

@if ($admin)
<div class="panel">
  <h2>Buat sesi baru</h2>
  <form method="post" action="{{ route('kelola.jadwal.sesi.store') }}" class="row" style="margin-top:10px">@csrf
    <input type="hidden" name="tahap_id" value="{{ $tahap->id }}">
    <label class="f">Kode<input type="text" name="kode" value="{{ old('kode', $kodeBerikut) }}" required></label>
    <label class="f">Tanggal<input type="date" name="tanggal" value="{{ old('tanggal') }}" required></label>
    <label class="f">Mulai<input type="time" name="jam_mulai" value="{{ old('jam_mulai', '08:00') }}" required></label>
    <label class="f">Selesai<input type="time" name="jam_selesai" value="{{ old('jam_selesai', '10:00') }}" required></label>
    <label class="f">Ruang<select name="ruang_id" required>@foreach ($ruang as $r)<option value="{{ $r->id }}" @selected(old('ruang_id') == $r->id)>{{ $r->nama }}</option>@endforeach</select></label>
    <label class="f">Jumlah mahasiswa<input type="number" name="kapasitas" min="1" max="10" value="{{ old('kapasitas', 3) }}" required></label>
    <button class="btn">Buat sesi</button>
  </form>
  @if ($ruang->isEmpty())<p class="hint">Tambahkan ruang dulu di menu Ruang.</p>@endif
</div>
@endif
@endsection
