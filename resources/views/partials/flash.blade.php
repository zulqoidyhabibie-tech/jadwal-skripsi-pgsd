@if (session('ok'))<div class="flash ok" role="status">{{ session('ok') }}</div>@endif
@if (session('err'))<div class="flash err" role="alert">{{ session('err') }}</div>@endif
@if ($errors->any())
<div class="errors" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
