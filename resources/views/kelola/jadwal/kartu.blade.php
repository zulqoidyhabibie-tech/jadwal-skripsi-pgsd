<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kartu ujian {{ $j->pendaftaran->mahasiswa->nim }} · Siskripsi PGSD</title>
<style>
  body{font-family:"Times New Roman",Times,serif;color:#111;background:#eee;margin:0;padding:24px 16px}
  .kartu{background:#fff;max-width:720px;margin:0 auto;padding:32px 40px;border:1px solid #ccc}
  .kop{text-align:center;border-bottom:3px double #111;padding-bottom:10px;margin-bottom:18px}
  .kop b{display:block;font-size:17px;text-transform:uppercase}
  h1{text-align:center;font-size:18px;text-decoration:underline;margin:0 0 18px}
  table{width:100%;border-collapse:collapse;font-size:15px}
  td{padding:5px 4px;vertical-align:top}
  td:first-child{width:170px} td:nth-child(2){width:12px}
  .ttd{display:flex;justify-content:flex-end;margin-top:36px;font-size:15px}
  .ttd div{text-align:center;width:260px}
  .aksi{max-width:720px;margin:0 auto 12px;display:flex;justify-content:flex-end;font-family:system-ui,sans-serif}
  .aksi button{font:inherit;font-weight:700;border:0;border-radius:8px;padding:9px 16px;background:#1d6b4f;color:#fff;cursor:pointer}
  @media print{body{background:#fff;padding:0}.kartu{border:0;padding:0}.aksi{display:none}}
</style>
</head>
<body>
@php($s = $j->sesi)
@php($m = $j->pendaftaran->mahasiswa)
<div class="aksi"><button onclick="window.print()">Cetak kartu</button></div>
<div class="kartu">
  <div class="kop"><b>Program Studi Pendidikan Guru Sekolah Dasar</b><b>Universitas Muhammadiyah Muara Bungo</b></div>
  <h1>KARTU {{ strtoupper($s->tahap->nama) }}</h1>
  <table>
    <tr><td>Kode ujian</td><td>:</td><td>{{ $s->kode }}</td></tr>
    <tr><td>Nama mahasiswa</td><td>:</td><td>{{ $m->nama }}</td></tr>
    <tr><td>NIM</td><td>:</td><td>{{ $m->nim }}</td></tr>
    <tr><td>Judul</td><td>:</td><td>{{ $m->skripsi?->judul }}</td></tr>
    <tr><td>Hari, tanggal</td><td>:</td><td>{{ $s->tanggalLengkap() }}</td></tr>
    <tr><td>Waktu</td><td>:</td><td>{{ str_replace(':', '.', $s->waktu()) }} WIB</td></tr>
    <tr><td>Ruang</td><td>:</td><td>{{ $s->ruang->nama }}</td></tr>
    <tr><td>Pembimbing 1</td><td>:</td><td>{{ $m->skripsi?->pembimbing1?->nama }}</td></tr>
    <tr><td>Pembimbing 2</td><td>:</td><td>{{ $m->skripsi?->pembimbing2?->nama }}</td></tr>
    <tr><td>Penguji 1</td><td>:</td><td>{{ $j->penguji1->nama }}</td></tr>
    <tr><td>Penguji 2</td><td>:</td><td>{{ $j->penguji2->nama }}</td></tr>
  </table>
  <div class="ttd"><div>Muara Bungo, {{ now()->translatedFormat('d F Y') }}<br>Ketua Program Studi PGSD<br><br><br><br>(...................................)</div></div>
</div>
</body>
</html>
