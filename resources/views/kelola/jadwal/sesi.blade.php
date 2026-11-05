@extends('layouts.app')
@section('title', 'Sesi '.$sesi->kode)
@section('content')
@php($admin = auth()->user()->hasRole('admin'))
<div>
  <a href="{{ route('kelola.jadwal.index', ['tahap' => $sesi->tahap->kode]) }}" class="hint">&larr; Jadwal {{ strtolower($sesi->tahap->nama) }}</a>
  <h1>Sesi {{ $sesi->kode }}</h1>
  <p class="sub">{{ $sesi->tanggalLengkap() }} · {{ $sesi->waktu() }} · {{ $sesi->ruang->nama }} · {{ $sesi->jadwal->count() }} dari {{ $sesi->kapasitas }} slot terisi ·
    {!! $sesi->terbit ? '<span class="pill ok">Terbit</span>' : '<span class="pill off">Draf, belum terlihat mahasiswa dan dosen</span>' !!}</p>
</div>
@if ($bentrok)<div class="flash err">Bentrok: {{ implode(' ', $bentrok) }}</div>@endif

<div class="panel">
  <div class="panel-head"><h2>Mahasiswa di sesi ini</h2>
    @if ($admin)<form method="post" action="{{ route('kelola.jadwal.sesi.terbitkan', $sesi) }}">@csrf<button class="btn sm {{ $sesi->terbit ? 'ghost' : '' }}">{{ $sesi->terbit ? 'Tarik jadi draf' : 'Terbitkan jadwal' }}</button></form>@endif
  </div>
  <div class="scroll"><table>
    <thead><tr><th>Mahasiswa</th><th>Pembimbing</th><th>Penguji</th><th>Hasil</th><th></th></tr></thead>
    <tbody>
    @forelse ($sesi->jadwal as $j)
      @php($m = $j->pendaftaran->mahasiswa)
      <tr>
        <td><b>{{ $m->nama }}</b><div class="mono">{{ $m->nim }}</div><div class="hint">{{ $m->skripsi?->judul }}</div></td>
        <td>1. {{ $m->skripsi?->pembimbing1?->nama }}<br>2. {{ $m->skripsi?->pembimbing2?->nama }}</td>
        <td>1. {{ $j->penguji1->nama }}<br>2. {{ $j->penguji2->nama }}</td>
        <td style="min-width:200px">
          <form method="post" action="{{ route('kelola.jadwal.hasil', $j) }}" style="display:grid;gap:6px">@csrf
            <select name="hasil" aria-label="Hasil ujian {{ $m->nama }}"><option value="">Belum ada hasil</option>@foreach (\App\Models\JadwalUjian::HASIL as $k => $l)<option value="{{ $k }}" @selected($j->hasil === $k)>{{ $l }}</option>@endforeach</select>
            <input type="text" name="catatan_hasil" value="{{ $j->catatan_hasil }}" placeholder="Catatan (opsional)">
            <button class="btn sm ghost">Simpan hasil</button>
          </form>
        </td>
        <td style="white-space:nowrap">
          <a class="btn sm ghost" href="{{ route('jadwal.kartu', $j) }}" target="_blank">Kartu ujian</a>
          @if ($admin && ! $j->hasil)
          <form class="inline" method="post" action="{{ route('kelola.jadwal.slot.destroy', $j) }}">@csrf @method('delete')<button class="btn sm bad">Keluarkan</button></form>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="sub">Belum ada mahasiswa di sesi ini.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>

@if ($admin && $sesi->sisaSlot() > 0)
<div class="panel">
  <h2>Isi slot kosong</h2>
  <p class="sub">Penguji disarankan dari dosen yang bebannya paling ringan dan bukan pembimbing mahasiswa itu.{{ $sesi->tahap->kode === 'sidang' ? ' Untuk sidang, penguji seminar proposal dipakai lagi bila ada.' : '' }} Bila dosen bentrok dengan sesi lain di jam yang sama, sistem akan menolak.</p>
  @forelse ($siap as $p)
    @php($sk = $p->mahasiswa->skripsi)
    @php($sr = $saran[$p->id] ?? [null, null])
    <form method="post" action="{{ route('kelola.jadwal.slot.store', $sesi) }}" class="row" style="padding:12px 0;border-top:1px solid var(--line)">@csrf
      <input type="hidden" name="pendaftaran_id" value="{{ $p->id }}">
      <div style="flex:2"><b>{{ $p->mahasiswa->nama }}</b> <span class="mono">{{ $p->mahasiswa->nim }}</span>
        <div class="hint">Pembimbing: {{ $sk?->pembimbing1?->nama }} dan {{ $sk?->pembimbing2?->nama }}</div></div>
      <label class="f">Penguji 1<select name="penguji_1_id">
        @foreach ($kandidat1 as $k)@continue(in_array($k['dosen']->id, [$sk?->pembimbing_1_id, $sk?->pembimbing_2_id]))
          <option value="{{ $k['dosen']->id }}" @selected($sr[0] === $k['dosen']->id)>{{ $k['dosen']->nama }} — {{ $k['beban'] }}{{ $k['kuota'] ? '/'.$k['kuota'] : '' }}</option>@endforeach
      </select></label>
      <label class="f">Penguji 2<select name="penguji_2_id">
        @foreach ($kandidat2 as $k)@continue(in_array($k['dosen']->id, [$sk?->pembimbing_1_id, $sk?->pembimbing_2_id]))
          <option value="{{ $k['dosen']->id }}" @selected($sr[1] === $k['dosen']->id)>{{ $k['dosen']->nama }} — {{ $k['beban'] }}{{ $k['kuota'] ? '/'.$k['kuota'] : '' }}</option>@endforeach
      </select></label>
      <button class="btn">Masukkan</button>
    </form>
  @empty
    <p class="sub">Tidak ada mahasiswa terverifikasi yang menunggu jadwal untuk tahap ini.</p>
  @endforelse
</div>
@endif

@if ($admin)
<div class="panel">
  <details><summary>Ubah atau hapus sesi</summary>
    <form method="post" action="{{ route('kelola.jadwal.sesi.update', $sesi) }}" class="row" style="margin-top:10px">@csrf @method('put')
      <input type="hidden" name="tahap_id" value="{{ $sesi->tahap_id }}">
      <label class="f">Kode<input type="text" name="kode" value="{{ $sesi->kode }}" required></label>
      <label class="f">Tanggal<input type="date" name="tanggal" value="{{ $sesi->tanggal->format('Y-m-d') }}" required></label>
      <label class="f">Mulai<input type="time" name="jam_mulai" value="{{ substr($sesi->jam_mulai, 0, 5) }}" required></label>
      <label class="f">Selesai<input type="time" name="jam_selesai" value="{{ substr($sesi->jam_selesai, 0, 5) }}" required></label>
      <label class="f">Ruang<select name="ruang_id">@foreach ($ruang as $r)<option value="{{ $r->id }}" @selected($sesi->ruang_id === $r->id)>{{ $r->nama }}</option>@endforeach</select></label>
      <label class="f">Jumlah mahasiswa<input type="number" name="kapasitas" min="1" max="10" value="{{ $sesi->kapasitas }}" required></label>
      <button class="btn">Simpan</button>
    </form>
    <form method="post" action="{{ route('kelola.jadwal.sesi.destroy', $sesi) }}" style="margin-top:12px">@csrf @method('delete')
      <button class="btn bad sm">Hapus sesi ini</button> <span class="hint">Mahasiswanya kembali ke daftar siap dijadwalkan.</span></form>
  </details>
</div>
@endif
@endsection
