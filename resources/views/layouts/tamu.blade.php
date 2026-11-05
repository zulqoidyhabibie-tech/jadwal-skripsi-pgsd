<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') · Siskripsi PGSD</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="tamu">
  <div class="panel" style="display:grid;gap:16px">
    <div><div class="brand">Siskripsi PGSD</div><p class="sub">Pengajuan judul, seminar proposal, dan sidang skripsi</p></div>
    @include('partials.flash')
    @yield('content')
  </div>
</div>
</body>
</html>
