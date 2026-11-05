@extends('layouts.app')
@section('title', 'Periode')
@section('content')
<div><h1>Periode akademik</h1><p class="sub">Pendaftaran baru dicatat pada periode yang aktif.</p></div>
<div class="panel"><div class="scroll"><table>
  <thead><tr><th>Periode</th><th>Status</th><th></th></tr></thead>
  <tbody>
  @foreach ($periode as $p)
    <tr><td><b>{{ $p->nama }}</b></td><td>{!! $p->aktif ? '<span class="pill ok">Aktif</span>' : '<span class="pill off">Tidak aktif</span>' !!}</td>
    <td>@unless ($p->aktif)<form method="post" action="{{ route('admin.periode.aktifkan', $p) }}">@csrf<button class="btn sm ghost">Jadikan aktif</button></form>@endunless</td></tr>
  @endforeach
  </tbody>
</table></div>
<form method="post" action="{{ route('admin.periode.store') }}" class="row" style="margin-top:14px">@csrf
  <label class="f">Periode baru<input type="text" name="nama" required placeholder="2026/2027 Genap"></label><button class="btn">Tambah</button></form>
</div>
@endsection
