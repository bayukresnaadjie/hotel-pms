<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>{{ config('hotel.nama') }} — @yield('title','Dashboard')</title>
<script src="https://cdn.tailwindcss.com"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>@media print{aside,header,.no-print{display:none!important}main{margin-left:0!important}body{background:#fff!important}}</style>
</head>
<body class="bg-stone-100 font-sans antialiased text-stone-800">
@php
$u = auth()->user();
$menu = [['route'=>'dashboard','label'=>'Dashboard','icon'=>'🏠']];

if ($u->punyaRole('admin','frontdesk')) {
    $menu[] = ['divider'=>'FRONT DESK'];
    $menu[] = ['route'=>'frontdesk.index','label'=>'Papan Front Desk','icon'=>'🛎️'];
    $menu[] = ['route'=>'reservasi.index','label'=>'Reservasi','icon'=>'📒'];
    $menu[] = ['route'=>'tamu.index','label'=>'Data Tamu','icon'=>'👥'];
}
$menu[] = ['divider'=>'KAMAR'];
$menu[] = ['route'=>'kamar.index','label'=>'Status Kamar','icon'=>'🛏️'];
$menu[] = ['route'=>'ketersediaan.index','label'=>'Ketersediaan','icon'=>'📅'];
if ($u->punyaRole('admin','frontdesk','housekeeping')) {
    $menu[] = ['route'=>'housekeeping.index','label'=>'Housekeeping','icon'=>'🧹'];
    $menu[] = ['route'=>'housekeeping.kerusakan','label'=>'Laporan Kerusakan','icon'=>'🔧'];
}
if ($u->punyaRole('admin','frontdesk','finance')) {
    $menu[] = ['divider'=>'KEUANGAN'];
    $menu[] = ['route'=>'nightaudit.index','label'=>'Night Audit','icon'=>'🌙'];
}
if ($u->punyaRole('admin','finance')) {
    $menu[] = ['route'=>'laporan.index','label'=>'Laporan','icon'=>'📈'];
}
if ($u->punyaRole('admin')) {
    $menu[] = ['divider'=>'MASTER DATA'];
    $menu[] = ['route'=>'master.tipe','label'=>'Tipe Kamar','icon'=>'🏷️'];
    $menu[] = ['route'=>'master.kamar','label'=>'Daftar Kamar','icon'=>'🚪'];
    $menu[] = ['route'=>'master.layanan','label'=>'Layanan','icon'=>'🍽️'];
    $menu[] = ['route'=>'master.tarif','label'=>'Tarif Musiman','icon'=>'🌤️'];
}
@endphp
<div class="flex min-h-screen">
<aside class="w-64 bg-stone-900 flex flex-col fixed inset-y-0 left-0 z-30">
    <div class="px-5 py-4 border-b border-stone-800">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-amber-500 rounded-xl flex items-center justify-center text-lg">🏨</div>
            <div class="min-w-0"><div class="font-bold text-white text-sm truncate">{{ config('hotel.nama') }}</div><div class="text-xs text-amber-400">Property Management</div></div>
        </div>
    </div>
    <nav class="flex-1 px-3 py-3 overflow-y-auto space-y-0.5">
        @foreach($menu as $item)
            @if(isset($item['divider']))
            <div class="pt-3 pb-1 px-3"><span class="text-xs font-semibold text-stone-500 tracking-wider">{{ $item['divider'] }}</span></div>
            @else
            @php $aktif = request()->routeIs($item['route'], $item['route'].'.*'); @endphp
            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $aktif ? 'bg-amber-500 text-stone-900' : 'text-stone-300 hover:bg-stone-800 hover:text-white' }}">
                <span>{{ $item['icon'] }}</span>{{ $item['label'] }}
            </a>
            @endif
        @endforeach
    </nav>
    <div class="px-4 py-3 border-t border-stone-800">
        <div class="flex items-center gap-2 px-2 mb-2">
            <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full flex-shrink-0"/>
            <div class="min-w-0"><div class="text-xs font-medium text-white truncate">{{ $u->name }}</div><div class="text-xs text-stone-400">{{ $u->role_badge['label'] }}</div></div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="w-full text-left px-3 py-1.5 text-xs text-stone-400 hover:text-red-300 hover:bg-stone-800 rounded-lg transition">🚪 Keluar</button>
        </form>
    </div>
</aside>
<main class="flex-1 ml-64 min-w-0">
    <header class="bg-white border-b border-stone-200 px-8 py-3.5 sticky top-0 z-10">
        <div class="flex items-center justify-between">
            <div><h1 class="text-base font-semibold text-stone-900">@yield('title','Dashboard')</h1>
            @hasSection('subtitle')<p class="text-xs text-stone-400">@yield('subtitle')</p>@endif</div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-stone-400 hidden xl:inline">{{ now()->translatedFormat('l, d F Y') }}</span>
                @yield('header-action')
            </div>
        </div>
    </header>
    <div class="px-8 pt-4">
        @foreach(['success'=>'green','error'=>'red','info'=>'blue'] as $tipe=>$warna)
        @if(session($tipe))<div class="bg-{{ $warna }}-50 border border-{{ $warna }}-200 text-{{ $warna }}-700 px-4 py-3 rounded-lg text-sm mb-4">{{ session($tipe) }}</div>@endif
        @endforeach
        @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-4"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    </div>
    <div class="px-8 py-5 pb-10">@yield('content')</div>
</main>
</div>
</body>
</html>
