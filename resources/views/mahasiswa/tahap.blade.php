@extends('layouts.app')
@section('title', $tahap->nama)
@section('content')
@php($ubah = $pendaftaran->bisaDiubah())
<div>
  <a href="{{ route('mhs.beranda') }}" class="hint">&larr; Kembali</a>
  <h1>{{ $tahap->nama }}</h1>
  <p class="sub">Status: {{ $pendaftaran->statusLabel() }}. Berkas cukup berupa link Google Drive yang bisa dibuka oleh prodi.</p>
</div>
@if ($pendaftaran->status === 'ditolak' && $pendaftaran->catatan)
  <div class="flash err">Perlu perbaikan: {{ $pendaftaran->catatan }}</div>
@endif
<form method="post" action="{{ route('mhs.simpan', $tahap->kode) }}" class="panel">
  @csrf
  @if ($tahap->kode === 'judul')
    <label class="f" style="margin-bottom:16px">Judul skripsi yang diajukan
      <textarea name="judul" required {{ $ubah ? '' : 'disabled' }}>{{ old('judul', $skripsi?->judul) }}</textarea>
    </label>
  @endif
  <div class="panel-head"><h2>Syarat</h2><span class="hint">Diatur oleh admin prodi</span></div>
  <div>
  @forelse ($cek['hasil'] as $h)
    @php($s = $h['syarat'])
    @php($i = $h['isian'])
    <div class="req">
      <span @class(['dot', 'ok' => $h['ok'], 'no' => ! $h['ok']])>{{ $h['ok'] ? '✓' : '' }}</span>
      <div>
        <div class="name">{{ $s->nama }} @unless($s->wajib)<span class="hint">(opsional)</span>@endunless</div>
        <div class="hint">
          @if ($s->jenis === 'angka') Minimal {{ rtrim(rtrim(number_format($s->nilai_minimal, 2, ',', ''), '0'), ',') }}
          @elseif ($s->jenis === 'link') Tempel link berkas
          @else Pernyataan @endif
          {{ $s->keterangan ? '· '.$s->keterangan : '' }}
        </div>
      </div>
      <div>
        @if ($s->jenis === 'centang')
          <label class="chk"><input type="checkbox" name="isian[{{ $s->id }}]" value="1" @checked(old("isian.$s->id", $i?->nilai) === '1') @disabled(! $ubah)> Ya, benar</label>
        @else
          <input type="{{ $s->jenis === 'link' ? 'url' : 'text' }}" name="isian[{{ $s->id }}]" value="{{ old("isian.$s->id", $i?->nilai) }}" placeholder="{{ $s->jenis === 'link' ? 'https://drive.google.com/…' : 'Contoh: 120' }}" @disabled(! $ubah) @if($s->jenis==='angka') inputmode="decimal" @endif>
        @endif
      </div>
      <div>
        @if ($i?->status === 'ditolak')<span class="pill bad">Ditolak</span>@if($i->catatan)<div class="hint">{{ $i->catatan }}</div>@endif
        @elseif ($i?->status === 'diterima')<span class="pill ok">Diterima</span>
        @elseif ($h['ok'])<span class="pill ok">Terpenuhi</span>
        @elseif ($i?->nilai)<span class="pill bad">Belum memenuhi</span>
        @else<span class="pill off">Belum diisi</span>@endif
      </div>
    </div>
  @empty
    <p class="sub">Belum ada syarat untuk tahap ini.</p>
  @endforelse
  </div>
  @if ($ubah)
  <div class="foot">
    <span class="sub">{{ $cek['lengkap'] ? 'Semua syarat wajib terpenuhi. Anda bisa mendaftar sekarang.' : 'Simpan isian Anda. Tombol Daftar aktif setelah semua syarat wajib terpenuhi.' }}</span>
    <div class="row" style="flex:0 0 auto">
      <button class="btn ghost" name="aksi" value="simpan">Simpan</button>
      <button class="btn" name="aksi" value="ajukan" @disabled(! $cek['lengkap'])>Daftar {{ strtolower($tahap->nama) }}</button>
    </div>
  </div>
  @endif
</form>
@endsection
